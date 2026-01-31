<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\SocialAccount;
use App\Models\Post;
use App\Services\LeadCaptureService;
use App\Services\WhatsAppAutomationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WebhookController extends Controller
{
    protected $leadCaptureService;
    protected $whatsappService;

    public function __construct(LeadCaptureService $leadCaptureService, WhatsAppAutomationService $whatsappService)
    {
        $this->leadCaptureService = $leadCaptureService;
        $this->whatsappService = $whatsappService;
    }

    /**
     * Facebook/Instagram webhook handler
     */
    public function facebookWebhook(Request $request)
    {
        // Verify webhook
        if ($request->get('hub_mode') === 'subscribe') {
            $challenge = $request->get('hub_challenge');
            $verifyToken = $request->get('hub_verify_token');
            
            if ($verifyToken === config('services.facebook.webhook_verify_token')) {
                return response($challenge, 200);
            }
            return response('Verification failed', 403);
        }

        // Process webhook events
        $data = $request->json()->all();
        Log::info('Facebook webhook received', ['data' => $data]);

        if (isset($data['entry'])) {
            foreach ($data['entry'] as $entry) {
                if (isset($entry['messaging'])) {
                    foreach ($entry['messaging'] as $messaging) {
                        $this->processFacebookMessage($messaging);
                    }
                } elseif (isset($entry['changes'])) {
                    foreach ($entry['changes'] as $change) {
                        if ($change['field'] === 'feed') {
                            $this->processFacebookFeedChange($change);
                        }
                    }
                }
            }
        }

        return response('OK', 200);
    }

    /**
     * WhatsApp webhook handler
     */
    public function whatsappWebhook(Request $request)
    {
        // Verify webhook
        if ($request->get('hub_mode') === 'subscribe') {
            $challenge = $request->get('hub_challenge');
            $verifyToken = $request->get('hub_verify_token');
            
            if ($verifyToken === config('services.whatsapp.webhook_verify_token')) {
                return response($challenge, 200);
            }
            return response('Verification failed', 403);
        }

        $data = $request->json()->all();
        Log::info('WhatsApp webhook received', ['data' => $data]);

        if (isset($data['entry'])) {
            foreach ($data['entry'] as $entry) {
                if (isset($entry['changes'])) {
                    foreach ($entry['changes'] as $change) {
                        if ($change['field'] === 'messages') {
                            $this->processWhatsAppMessage($change);
                        }
                    }
                }
            }
        }

        return response('OK', 200);
    }

    /**
     * X webhook handler
     */
    public function xWebhook(Request $request)
    {
        // For X webhooks, we need to handle CRC (Challenge-Response Check)
        if ($request->has('crc_token')) {
            $crcToken = $request->get('crc_token');
            $consumerSecret = config('services.x.consumer_secret');
            
            $signature = hash_hmac('sha256', $crcToken, $consumerSecret, true);
            $responseToken = base64_encode($signature);
            
            return response()->json(['response_token' => $responseToken]);
        }

        $data = $request->json()->all();
        Log::info('X webhook received', ['data' => $data]);

        // Process X events
        if (isset($data['direct_message_events'])) {
            foreach ($data['direct_message_events'] as $event) {
                $this->processXDirectMessage($event);
            }
        }

        if (isset($data['tweet_create_events'])) {
            foreach ($data['tweet_create_events'] as $tweet) {
                $this->processXMention($tweet);
            }
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Check if message contains lead indicators
     */
    private function containsLeadIndicators($message)
    {
        if (empty($message)) {
            return false;
        }

        // Check for email patterns
        if (preg_match('/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', $message)) {
            return true;
        }

        // Check for phone patterns
        if (preg_match('/\b(?:\+?1[-.\s]?)?\(?[0-9]{3}\)?[-.\s]?[0-9]{3}[-.\s]?[0-9]{4}\b/', $message)) {
            return true;
        }

        // Check for lead keywords
        $leadKeywords = [
            'interested', 'quote', 'price', 'cost', 'how much', 'contact me',
            'call me', 'email me', 'send info', 'learn more', 'sign up',
            'get started', 'free trial', 'demo', 'consultation', 'appointment'
        ];

        $messageLower = strtolower($message);
        foreach ($leadKeywords as $keyword) {
            if (strpos($messageLower, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }
}
