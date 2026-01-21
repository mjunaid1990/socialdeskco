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
     * Twitter webhook handler
     */
    public function twitterWebhook(Request $request)
    {
        // For Twitter webhooks, we need to handle CRC (Challenge-Response Check)
        if ($request->has('crc_token')) {
            $crcToken = $request->get('crc_token');
            $consumerSecret = config('services.twitter.consumer_secret');
            
            $signature = hash_hmac('sha256', $crcToken, $consumerSecret, true);
            $responseToken = base64_encode($signature);
            
            return response()->json(['response_token' => 'sha256=' . $responseToken]);
        }

        $data = $request->json()->all();
        Log::info('Twitter webhook received', ['data' => $data]);

        // Process Twitter events
        if (isset($data['direct_message_events'])) {
            foreach ($data['direct_message_events'] as $event) {
                $this->processTwitterDirectMessage($event);
            }
        }

        if (isset($data['tweet_create_events'])) {
            foreach ($data['tweet_create_events'] as $tweet) {
                $this->processTwitterMention($tweet);
            }
        }

        return response('OK', 200);
    }

    /**
     * Process Facebook/Instagram message
     */
    private function processFacebookMessage($messaging)
    {
        try {
            $senderId = $messaging['sender']['id'];
            $recipientId = $messaging['recipient']['id'];
            
            // Find the social account
            $socialAccount = SocialAccount::where('account_id', $recipientId)
                ->where('platform', 'facebook')
                ->first();

            if (!$socialAccount) {
                Log::warning('Social account not found for Facebook message', ['recipient_id' => $recipientId]);
                return;
            }

            // Extract message content
            $message = '';
            if (isset($messaging['message']['text'])) {
                $message = $messaging['message']['text'];
            }

            // Check for lead indicators (email, phone, keywords)
            if ($this->containsLeadIndicators($message)) {
                $this->leadCaptureService->captureFromMessage(
                    $socialAccount,
                    $senderId,
                    $message,
                    'facebook',
                    'dm'
                );
            }

        } catch (\Exception $e) {
            Log::error('Error processing Facebook message', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Process Facebook feed changes (comments)
     */
    private function processFacebookFeedChange($change)
    {
        try {
            if ($change['value']['verb'] === 'add' && $change['value']['item'] === 'comment') {
                $comment = $change['value'];
                $postId = $comment['post_id'];
                $commentId = $comment['comment_id'];
                $message = $comment['message'];
                $from = $comment['from'];

                // Find the social account and post
                $post = Post::where('platform_post_id', $postId)->first();
                if (!$post) {
                    Log::warning('Post not found for Facebook comment', ['post_id' => $postId]);
                    return;
                }

                $socialAccount = $post->socialAccount;
                if (!$socialAccount) {
                    return;
                }

                // Check for lead indicators
                if ($this->containsLeadIndicators($message)) {
                    $this->leadCaptureService->captureFromComment(
                        $socialAccount,
                        $post,
                        $from,
                        $commentId,
                        $message,
                        'facebook'
                    );
                }

            }
        } catch (\Exception $e) {
            Log::error('Error processing Facebook feed change', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Process WhatsApp message
     */
    private function processWhatsAppMessage($change)
    {
        try {
            $messages = $change['value']['messages'] ?? [];
            
            foreach ($messages as $message) {
                if ($message['type'] === 'text') {
                    $phoneNumber = $message['from'];
                    $text = $message['text']['body'];
                    $metadata = $change['value']['metadata'];
                    
                    // Find the social account
                    $socialAccount = SocialAccount::where('phone_number_id', $metadata['phone_number_id'])
                        ->where('platform', 'whatsapp')
                        ->first();

                    if (!$socialAccount) {
                        Log::warning('Social account not found for WhatsApp message', ['phone_number_id' => $metadata['phone_number_id']]);
                        return;
                    }

                    // Check for lead indicators
                    if ($this->containsLeadIndicators($text)) {
                        $this->leadCaptureService->captureFromMessage(
                            $socialAccount,
                            $phoneNumber,
                            $text,
                            'whatsapp',
                            'dm'
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error processing WhatsApp message', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Process Twitter direct message
     */
    private function processTwitterDirectMessage($event)
    {
        try {
            if ($event['message_create']['sender_id'] !== $event['message_create']['target']['recipient_id']) {
                $senderId = $event['message_create']['sender_id'];
                $recipientId = $event['message_create']['target']['recipient_id'];
                $message = $event['message_create']['message_data']['text'];

                // Find the social account
                $socialAccount = SocialAccount::where('account_id', $recipientId)
                    ->where('platform', 'twitter')
                    ->first();

                if (!$socialAccount) {
                    Log::warning('Social account not found for Twitter DM', ['recipient_id' => $recipientId]);
                    return;
                }

                // Check for lead indicators
                if ($this->containsLeadIndicators($message)) {
                    $this->leadCaptureService->captureFromMessage(
                        $socialAccount,
                        $senderId,
                        $message,
                        'twitter',
                        'dm'
                    );
                }
            }
        } catch (\Exception $e) {
            Log::error('Error processing Twitter direct message', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Process Twitter mention
     */
    private function processTwitterMention($tweet)
    {
        try {
            // Check if this is a mention of our account
            $socialAccounts = SocialAccount::where('platform', 'twitter')->get();
            
            foreach ($socialAccounts as $account) {
                if (strpos($tweet['text'], '@' . $account->username) !== false) {
                    // This is a mention, check for lead indicators
                    if ($this->containsLeadIndicators($tweet['text'])) {
                        $this->leadCaptureService->captureFromComment(
                            $account,
                            null,
                            $tweet['user'],
                            $tweet['id_str'],
                            $tweet['text'],
                            'twitter'
                        );
                    }
                    break;
                }
            }
        } catch (\Exception $e) {
            Log::error('Error processing Twitter mention', ['error' => $e->getMessage()]);
        }
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
