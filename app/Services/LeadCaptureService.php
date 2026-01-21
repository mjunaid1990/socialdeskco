<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\SocialAccount;
use App\Models\Post;
use App\Models\AutomationRule;
use App\Services\WhatsAppAutomationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class LeadCaptureService
{
    protected $whatsappService;

    public function __construct(WhatsAppAutomationService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Capture lead from message (DM)
     */
    public function captureFromMessage(SocialAccount $socialAccount, $platformSenderId, $message, $platform, $sourceType)
    {
        try {
            // Extract contact information from message
            $contactInfo = $this->extractContactInfo($message);
            
            // Check if lead already exists
            $existingLead = Lead::where('platform', $platform)
                ->where('platform_lead_id', $platformSenderId)
                ->first();

            if ($existingLead) {
                // Update existing lead
                $this->updateLeadFromMessage($existingLead, $contactInfo, $message);
                return $existingLead;
            }

            // Create new lead
            $lead = Lead::create([
                'user_id' => $socialAccount->user_id,
                'social_account_id' => $socialAccount->id,
                'first_name' => $contactInfo['firstName'] ?? 'Unknown',
                'last_name' => $contactInfo['lastName'] ?? '',
                'email' => $contactInfo['email'] ?? null,
                'phone' => $contactInfo['phone'] ?? null,
                'platform' => $platform,
                'platform_lead_id' => $platformSenderId,
                'source_type' => $sourceType,
                'message' => $message,
                'platform_data' => [
                    'raw_message' => $message,
                    'sender_id' => $platformSenderId,
                    'captured_at' => now()->toISOString()
                ],
                'status' => 'new',
                'custom_fields' => $contactInfo['customFields'] ?? []
            ]);

            Log::info('Lead captured from message', [
                'lead_id' => $lead->id,
                'platform' => $platform,
                'source_type' => $sourceType
            ]);

            // Trigger automation rules
            $this->triggerAutomationRules($lead);

            // Send WhatsApp follow-up if applicable
            if ($lead->phone && $platform !== 'whatsapp') {
                $this->whatsappService->sendInitialFollowUp($lead);
            }

            return $lead;

        } catch (\Exception $e) {
            Log::error('Error capturing lead from message', [
                'error' => $e->getMessage(),
                'platform' => $platform,
                'sender_id' => $platformSenderId
            ]);
            return null;
        }
    }

    /**
     * Capture lead from comment
     */
    public function captureFromComment(SocialAccount $socialAccount, ?Post $post, $from, $commentId, $message, $platform)
    {
        try {
            // Extract contact information from comment
            $contactInfo = $this->extractContactInfo($message);
            
            // Check if lead already exists
            $existingLead = Lead::where('platform', $platform)
                ->where('platform_lead_id', $from['id'] ?? $commentId)
                ->first();

            if ($existingLead) {
                // Update existing lead
                $this->updateLeadFromComment($existingLead, $contactInfo, $message, $post);
                return $existingLead;
            }

            // Create new lead
            $lead = Lead::create([
                'user_id' => $socialAccount->user_id,
                'social_account_id' => $socialAccount->id,
                'post_id' => $post?->id,
                'first_name' => $from['name'] ?? $contactInfo['firstName'] ?? 'Unknown',
                'last_name' => $contactInfo['lastName'] ?? '',
                'email' => $contactInfo['email'] ?? null,
                'phone' => $contactInfo['phone'] ?? null,
                'platform' => $platform,
                'platform_lead_id' => $from['id'] ?? $commentId,
                'source_type' => 'comment',
                'message' => $message,
                'platform_data' => [
                    'raw_comment' => $message,
                    'comment_id' => $commentId,
                    'from' => $from,
                    'post_id' => $post?->platform_post_id,
                    'captured_at' => now()->toISOString()
                ],
                'status' => 'new',
                'custom_fields' => $contactInfo['customFields'] ?? []
            ]);

            Log::info('Lead captured from comment', [
                'lead_id' => $lead->id,
                'platform' => $platform,
                'post_id' => $post?->id
            ]);

            // Trigger automation rules
            $this->triggerAutomationRules($lead);

            // Send WhatsApp follow-up if applicable
            if ($lead->phone) {
                $this->whatsappService->sendInitialFollowUp($lead);
            }

            return $lead;

        } catch (\Exception $e) {
            Log::error('Error capturing lead from comment', [
                'error' => $e->getMessage(),
                'platform' => $platform,
                'comment_id' => $commentId
            ]);
            return null;
        }
    }

    /**
     * Extract contact information from text
     */
    private function extractContactInfo($text)
    {
        $contactInfo = [
            'firstName' => null,
            'lastName' => null,
            'email' => null,
            'phone' => null,
            'customFields' => []
        ];

        // Extract email
        if (preg_match('/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', $text, $matches)) {
            $contactInfo['email'] = $matches[0];
        }

        // Extract phone numbers
        if (preg_match('/\b(?:\+?1[-.\s]?)?\(?[0-9]{3}\)?[-.\s]?[0-9]{3}[-.\s]?[0-9]{4}\b/', $text, $matches)) {
            $contactInfo['phone'] = preg_replace('/[^0-9+]/', '', $matches[0]);
        }

        // Extract name patterns
        if (preg_match('/(?:my name is|i am|i\'m)\s+([A-Za-z]+(?:\s+[A-Za-z]+)?)/i', $text, $matches)) {
            $nameParts = explode(' ', trim($matches[1]));
            $contactInfo['firstName'] = $nameParts[0];
            if (count($nameParts) > 1) {
                $contactInfo['lastName'] = implode(' ', array_slice($nameParts, 1));
            }
        }

        // Extract other potential lead information
        if (preg_match('/(?:interested in|looking for|want)\s+(.+?)(?:\.|$)/i', $text, $matches)) {
            $contactInfo['customFields']['interest'] = trim($matches[1]);
        }

        if (preg_match('/(?:budget|price range)\s+(.+?)(?:\.|$)/i', $text, $matches)) {
            $contactInfo['customFields']['budget'] = trim($matches[1]);
        }

        if (preg_match('/(?:location|based in|from)\s+(.+?)(?:\.|$)/i', $text, $matches)) {
            $contactInfo['customFields']['location'] = trim($matches[1]);
        }

        return $contactInfo;
    }

    /**
     * Update existing lead from message
     */
    private function updateLeadFromMessage(Lead $lead, $contactInfo, $message)
    {
        $lead->message = $lead->message . "\n\n" . $message;
        
        // Update contact info if empty
        if (!$lead->email && $contactInfo['email']) {
            $lead->email = $contactInfo['email'];
        }
        
        if (!$lead->phone && $contactInfo['phone']) {
            $lead->phone = $contactInfo['phone'];
        }

        // Update platform data
        $platformData = $lead->platform_data ?? [];
        $platformData['additional_messages'][] = [
            'message' => $message,
            'received_at' => now()->toISOString()
        ];
        $lead->platform_data = $platformData;

        $lead->save();

        Log::info('Lead updated from message', ['lead_id' => $lead->id]);
    }

    /**
     * Update existing lead from comment
     */
    private function updateLeadFromComment(Lead $lead, $contactInfo, $message, ?Post $post)
    {
        $lead->message = $lead->message . "\n\n" . $message;
        
        // Update contact info if empty
        if (!$lead->email && $contactInfo['email']) {
            $lead->email = $contactInfo['email'];
        }
        
        if (!$lead->phone && $contactInfo['phone']) {
            $lead->phone = $contactInfo['phone'];
        }

        // Update platform data
        $platformData = $lead->platform_data ?? [];
        $platformData['additional_comments'][] = [
            'comment' => $message,
            'post_id' => $post?->platform_post_id,
            'received_at' => now()->toISOString()
        ];
        $lead->platform_data = $platformData;

        $lead->save();

        Log::info('Lead updated from comment', ['lead_id' => $lead->id]);
    }

    /**
     * Trigger automation rules for new lead
     */
    private function triggerAutomationRules(Lead $lead)
    {
        try {
            $automationRules = AutomationRule::where('is_active', true)
                ->where('trigger_type', 'new_lead')
                ->where(function($query) use ($lead) {
                    $query->whereNull('platform_filter')
                          ->orWhere('platform_filter', $lead->platform);
                })
                ->get();

            foreach ($automationRules as $rule) {
                // Queue the automation rule for processing
                dispatch(new \App\Jobs\ProcessAutomationRuleJob($rule, $lead));
            }

        } catch (\Exception $e) {
            Log::error('Error triggering automation rules for lead', [
                'error' => $e->getMessage(),
                'lead_id' => $lead->id
            ]);
        }
    }

    /**
     * Calculate lead score based on available information
     */
    public function calculateLeadScore(Lead $lead)
    {
        $score = 0;

        // Base score for having contact info
        if ($lead->email) $score += 20;
        if ($lead->phone) $score += 20;
        if ($lead->first_name && $lead->first_name !== 'Unknown') $score += 10;

        // Source type scoring
        switch ($lead->source_type) {
            case 'dm':
                $score += 30;
                break;
            case 'comment':
                $score += 20;
                break;
            case 'lead_form':
                $score += 40;
                break;
        }

        // Platform scoring
        switch ($lead->platform) {
            case 'whatsapp':
                $score += 25;
                break;
            case 'facebook':
            case 'instagram':
                $score += 20;
                break;
            case 'linkedin':
                $score += 30;
                break;
        }

        // Message content scoring
        if ($lead->message) {
            $messageLower = strtolower($lead->message);
            
            // High-intent keywords
            if (strpos($messageLower, 'interested') !== false) $score += 15;
            if (strpos($messageLower, 'quote') !== false) $score += 15;
            if (strpos($messageLower, 'price') !== false) $score += 10;
            if (strpos($messageLower, 'how much') !== false) $score += 10;
            if (strpos($messageLower, 'contact me') !== false) $score += 20;
            if (strpos($messageLower, 'call me') !== false) $score += 20;
        }

        // Custom fields scoring
        if ($lead->custom_fields) {
            foreach ($lead->custom_fields as $key => $value) {
                if ($value) {
                    $score += 5;
                }
            }
        }

        return min($score, 100); // Cap at 100
    }

    /**
     * Qualify lead based on score and criteria
     */
    public function qualifyLead(Lead $lead)
    {
        $score = $this->calculateLeadScore($lead);
        
        if ($score >= 70) {
            return 'hot';
        } elseif ($score >= 40) {
            return 'warm';
        } else {
            return 'cold';
        }
    }
}
