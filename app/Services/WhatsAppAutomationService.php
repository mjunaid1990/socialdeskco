<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\SocialAccount;
use App\Models\AutomationRule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

class WhatsAppAutomationService
{
    /**
     * Send initial follow-up message to new lead
     */
    public function sendInitialFollowUp(Lead $lead)
    {
        try {
            // Find WhatsApp account for the user
            $whatsappAccount = SocialAccount::where('user_id', $lead->user_id)
                ->where('platform', 'whatsapp')
                ->where('is_active', true)
                ->first();

            if (!$whatsappAccount) {
                Log::info('No WhatsApp account found for follow-up', ['lead_id' => $lead->id]);
                return false;
            }

            // Check if lead has phone number
            if (!$lead->phone) {
                Log::info('Lead has no phone number for WhatsApp follow-up', ['lead_id' => $lead->id]);
                return false;
            }

            // Prepare personalized message
            $message = $this->generateInitialMessage($lead);
            
            // Send WhatsApp message
            $response = $this->sendWhatsAppMessage($whatsappAccount, $lead->phone, $message);

            if ($response) {
                // Update lead status
                $lead->status = 'contacted';
                $lead->contacted_at = now();
                $lead->save();

                // Schedule follow-up messages
                $this->scheduleFollowUps($lead, $whatsappAccount);

                Log::info('Initial WhatsApp follow-up sent', [
                    'lead_id' => $lead->id,
                    'phone' => $lead->phone
                ]);

                return true;
            }

        } catch (\Exception $e) {
            Log::error('Error sending initial WhatsApp follow-up', [
                'error' => $e->getMessage(),
                'lead_id' => $lead->id
            ]);
        }

        return false;
    }

    /**
     * Generate personalized initial message
     */
    private function generateInitialMessage(Lead $lead)
    {
        $firstName = $lead->first_name;
        if ($firstName === 'Unknown') {
            $firstName = 'there';
        }

        $platform = ucfirst($lead->platform);
        $sourceType = ucfirst(str_replace('_', ' ', $lead->source_type));

        $messages = [
            "Hi {$firstName}! Thanks for reaching out via {$platform} {$sourceType}. I'm here to help! How can I assist you today?",
            "Hello {$firstName}! I received your {$sourceType} on {$platform} and wanted to follow up personally. What specific information are you looking for?",
            "Hi {$firstName}! Thanks for your interest! I saw your {$sourceType} and wanted to connect. What's the best way I can help you?",
            "Hello {$firstName}! Great to hear from you! I noticed your {$sourceType} on {$platform}. What questions can I answer for you?"
        ];

        return $messages[array_rand($messages)];
    }

    /**
     * Send WhatsApp message
     */
    private function sendWhatsAppMessage(SocialAccount $account, $phoneNumber, $message)
    {
        try {
            // Clean phone number
            $phoneNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);
            if (!str_starts_with($phoneNumber, '+')) {
                $phoneNumber = '+' . $phoneNumber;
            }

            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $phoneNumber,
                'type' => 'text',
                'text' => [
                    'body' => $message
                ]
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $account->access_token,
                'Content-Type' => 'application/json',
            ])->post("https://graph.facebook.com/v18.0/{$account->phone_number_id}/messages", $payload);

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('WhatsApp message failed', [
                    'response' => $response->json(),
                    'status_code' => $response->status(),
                    'phone_number' => $phoneNumber
                ]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error('WhatsApp API Error', [
                'error' => $e->getMessage(),
                'phone_number' => $phoneNumber
            ]);
            return false;
        }
    }

    /**
     * Schedule follow-up messages
     */
    private function scheduleFollowUps(Lead $lead, SocialAccount $whatsappAccount)
    {
        // Schedule first follow-up after 24 hours
        dispatch(function() use ($lead, $whatsappAccount) {
            $this->sendFollowUpMessage($lead, $whatsappAccount, 'first_followup');
        })->delay(now()->addHours(24));

        // Schedule second follow-up after 3 days
        dispatch(function() use ($lead, $whatsappAccount) {
            $this->sendFollowUpMessage($lead, $whatsappAccount, 'second_followup');
        })->delay(now()->addDays(3));

        // Schedule final follow-up after 7 days
        dispatch(function() use ($lead, $whatsappAccount) {
            $this->sendFollowUpMessage($lead, $whatsappAccount, 'final_followup');
        })->delay(now()->addDays(7));
    }

    /**
     * Send follow-up message
     */
    public function sendFollowUpMessage(Lead $lead, SocialAccount $whatsappAccount, $followUpType)
    {
        try {
            // Check if lead is still in 'contacted' status
            if ($lead->status !== 'contacted') {
                Log::info('Skipping follow-up - lead status changed', [
                    'lead_id' => $lead->id,
                    'status' => $lead->status
                ]);
                return false;
            }

            $message = $this->generateFollowUpMessage($lead, $followUpType);
            
            $response = $this->sendWhatsAppMessage($whatsappAccount, $lead->phone, $message);

            if ($response) {
                Log::info('Follow-up message sent', [
                    'lead_id' => $lead->id,
                    'follow_up_type' => $followUpType
                ]);

                // Update platform data
                $platformData = $lead->platform_data ?? [];
                $platformData['whatsapp_followups'][] = [
                    'type' => $followUpType,
                    'sent_at' => now()->toISOString()
                ];
                $lead->platform_data = $platformData;
                $lead->save();

                return true;
            }

        } catch (\Exception $e) {
            Log::error('Error sending follow-up message', [
                'error' => $e->getMessage(),
                'lead_id' => $lead->id,
                'follow_up_type' => $followUpType
            ]);
        }

        return false;
    }

    /**
     * Generate follow-up message based on type
     */
    private function generateFollowUpMessage(Lead $lead, $followUpType)
    {
        $firstName = $lead->first_name;
        if ($firstName === 'Unknown') {
            $firstName = 'there';
        }

        switch ($followUpType) {
            case 'first_followup':
                return "Hi {$firstName}! Just checking in to see if you had any questions or if there's anything I can help you with. I'm here when you're ready!";

            case 'second_followup':
                return "Hello {$firstName}! Hope you're doing well. I wanted to follow up one more time to see if you're still interested. Is there a good time to chat?";

            case 'final_followup':
                return "Hi {$firstName}! This will be my last follow-up message. If you'd like to continue the conversation, please feel free to reach out anytime. Best regards!";

            default:
                return "Hi {$firstName}! Just checking in to see how you're doing.";
        }
    }

    /**
     * Send custom message to lead
     */
    public function sendCustomMessage(Lead $lead, $message)
    {
        try {
            $whatsappAccount = SocialAccount::where('user_id', $lead->user_id)
                ->where('platform', 'whatsapp')
                ->where('is_active', true)
                ->first();

            if (!$whatsappAccount || !$lead->phone) {
                return false;
            }

            $response = $this->sendWhatsAppMessage($whatsappAccount, $lead->phone, $message);

            if ($response) {
                // Update platform data
                $platformData = $lead->platform_data ?? [];
                $platformData['custom_messages'][] = [
                    'message' => $message,
                    'sent_at' => now()->toISOString()
                ];
                $lead->platform_data = $platformData;
                $lead->save();

                return true;
            }

        } catch (\Exception $e) {
            Log::error('Error sending custom WhatsApp message', [
                'error' => $e->getMessage(),
                'lead_id' => $lead->id
            ]);
        }

        return false;
    }

    /**
     * Send broadcast message to multiple leads
     */
    public function sendBroadcast($userId, $message, $filters = [])
    {
        try {
            $whatsappAccount = SocialAccount::where('user_id', $userId)
                ->where('platform', 'whatsapp')
                ->where('is_active', true)
                ->first();

            if (!$whatsappAccount) {
                return ['success' => false, 'message' => 'No WhatsApp account found'];
            }

            // Get leads based on filters
            $leadsQuery = Lead::where('user_id', $userId)
                ->where('status', '!=', 'lost')
                ->whereNotNull('phone');

            if (isset($filters['status'])) {
                $leadsQuery->where('status', $filters['status']);
            }

            if (isset($filters['platform'])) {
                $leadsQuery->where('platform', $filters['platform']);
            }

            if (isset($filters['source_type'])) {
                $leadsQuery->where('source_type', $filters['source_type']);
            }

            $leads = $leadsQuery->get();

            $sentCount = 0;
            $failedCount = 0;

            foreach ($leads as $lead) {
                $response = $this->sendWhatsAppMessage($whatsappAccount, $lead->phone, $message);
                
                if ($response) {
                    $sentCount++;
                    
                    // Update platform data
                    $platformData = $lead->platform_data ?? [];
                    $platformData['broadcast_messages'][] = [
                        'message' => $message,
                        'sent_at' => now()->toISOString()
                    ];
                    $lead->platform_data = $platformData;
                    $lead->save();
                } else {
                    $failedCount++;
                }
            }

            return [
                'success' => true,
                'sent' => $sentCount,
                'failed' => $failedCount,
                'total' => $leads->count()
            ];

        } catch (\Exception $e) {
            Log::error('Error sending WhatsApp broadcast', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
