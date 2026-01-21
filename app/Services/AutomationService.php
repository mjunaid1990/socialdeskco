<?php

namespace App\Services;

use App\Models\AutomationRule;
use App\Models\Engagement;
use App\Models\Lead;
use App\Helpers\SettingsHelper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\SpamProtectionService;
use App\Services\DuplicateEngagementService;
use App\Services\RateLimitService;

class AutomationService
{
    public function __construct(
        private SpamProtectionService $spamProtection,
        private DuplicateEngagementService $duplicateService,
        private RateLimitService $rateLimit
    ) {}

    public function processEngagement(Engagement $engagement)
    {
        // Check if engagement is spam
        if ($this->spamProtection->isSpam($engagement)) {
            Log::info('Skipping automation for spam engagement', [
                'engagement_id' => $engagement->id,
                'actor_username' => $engagement->actor_username,
            ]);
            return false;
        }

        // Check if engagement is duplicate
        if ($this->duplicateService->isDuplicate($engagement)) {
            Log::info('Skipping automation for duplicate engagement', [
                'engagement_id' => $engagement->id,
                'actor_username' => $engagement->actor_username,
            ]);
            return false;
        }

        // Prevent duplicate processing
        if ($this->duplicateService->preventDuplicateProcessing($engagement)) {
            Log::info('Engagement already being processed', [
                'engagement_id' => $engagement->id,
            ]);
            return false;
        }

        try {
            // Find applicable automation rules for this engagement
            $rules = AutomationRule::where('social_account_id', $engagement->social_account_id)
                ->where('is_active', true)
                ->get();

            foreach ($rules as $rule) {
                if ($this->shouldTriggerRule($rule, $engagement)) {
                    $this->executeAction($rule, $engagement);
                }
            }

            return true;
        } finally {
            // Always release the processing lock
            $this->duplicateService->releaseProcessingLock($engagement);
        }
    }

    private function shouldTriggerRule(AutomationRule $rule, Engagement $engagement)
    {
        $conditions = $rule->trigger_conditions;

        switch ($rule->trigger_type) {
            case 'keyword_match':
                return $this->checkKeywordMatch($conditions, $engagement);
            case 'engagement_type':
                return $this->checkEngagementType($conditions, $engagement);
            case 'time_based':
                return $this->checkTimeBased($conditions, $engagement);
            case 'engagement_frequency':
                return $this->checkEngagementFrequency($conditions, $engagement);
            default:
                return false;
        }
    }

    private function checkKeywordMatch(array $conditions, Engagement $engagement)
    {
        $keywords = $conditions['keywords'] ?? [];
        $content = strtolower($engagement->content ?? '');

        foreach ($keywords as $keyword) {
            if (str_contains($content, strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }

    private function checkEngagementType(array $conditions, Engagement $engagement)
    {
        $expectedTypes = $conditions['types'] ?? [];
        return in_array($engagement->engagement_type, $expectedTypes);
    }

    private function checkTimeBased(array $conditions, Engagement $engagement)
    {
        $timeCondition = $conditions['time'] ?? null;
        if (!$timeCondition) {
            return false;
        }

        // Implement time-based checks
        $currentTime = now();
        $engagementTime = $engagement->engaged_at ?? now();

        // Example: Check if engagement happened during business hours
        if (isset($timeCondition['business_hours'])) {
            $hour = $engagementTime->hour;
            return $hour >= 9 && $hour <= 17; // 9 AM to 5 PM
        }

        return true;
    }

    private function checkEngagementFrequency(array $conditions, Engagement $engagement)
    {
        $frequency = $conditions['frequency'] ?? 1;
        $period = $conditions['period'] ?? 'day'; // day, week, month

        $startTime = now();
        switch ($period) {
            case 'day':
                $startTime = $startTime->subDay();
                break;
            case 'week':
                $startTime = $startTime->subWeek();
                break;
            case 'month':
                $startTime = $startTime->subMonth();
                break;
        }

        $recentEngagements = Engagement::where('actor_username', $engagement->actor_username)
            ->whereBetween('created_at', [$startTime, now()])
            ->count();

        return $recentEngagements >= $frequency;
    }

    private function executeAction(AutomationRule $rule, Engagement $engagement)
    {
        // Check rate limits before executing action
        if (!$this->rateLimit->checkRateLimit($engagement->platform, $rule->action_type, $engagement->actor_platform_id)) {
            Log::warning('Rate limit exceeded for automation action', [
                'rule_id' => $rule->id,
                'action_type' => $rule->action_type,
                'platform' => $engagement->platform,
                'actor_platform_id' => $engagement->actor_platform_id,
            ]);
            return false;
        }

        // Check automation rule specific limits
        if (!$this->rateLimit->checkAutomationRuleLimit($rule)) {
            Log::warning('Automation rule rate limit exceeded', [
                'rule_id' => $rule->id,
                'rule_name' => $rule->name,
            ]);
            return false;
        }

        // Check cooldown period
        if (!$this->rateLimit->checkCooldownPeriod($engagement, $rule->action_type)) {
            Log::info('Action in cooldown period', [
                'rule_id' => $rule->id,
                'action_type' => $rule->action_type,
                'actor_platform_id' => $engagement->actor_platform_id,
            ]);
            return false;
        }

        switch ($rule->action_type) {
            case 'auto_reply':
                return $this->executeAutoReply($rule, $engagement);
            case 'tag_lead':
                return $this->executeTagLead($rule, $engagement);
            case 'forward_to_crm':
                return $this->executeForwardToCRM($rule, $engagement);
            case 'auto_like':
                return $this->executeAutoLike($rule, $engagement);
            case 'auto_dm':
                return $this->executeAutoDM($rule, $engagement);
            default:
                Log::warning("Unknown automation action type: {$rule->action_type}");
                return false;
        }
    }

    private function executeAutoReply(AutomationRule $rule, Engagement $engagement)
    {
        $replyText = $rule->action_parameters['reply_text'] ?? '';
        $delay = $rule->action_parameters['delay'] ?? 0; // Delay in seconds

        if ($delay > 0) {
            sleep($delay);
        }

        try {
            $account = $engagement->socialAccount;
            
            // Prepare response based on platform
            switch ($account->platform) {
                case 'facebook':
                    return $this->replyToFacebook($engagement, $replyText, $account);
                case 'instagram':
                    return $this->replyToInstagram($engagement, $replyText, $account);
                case 'twitter':
                    return $this->replyToTwitter($engagement, $replyText, $account);
                case 'x':
                    return $this->replyToX($engagement, $replyText, $account);
                case 'tiktok':
                    return $this->replyToTikTok($engagement, $replyText, $account);
                case 'youtube':
                    return $this->replyToYouTube($engagement, $replyText, $account);
                default:
                    return false;
            }
        } catch (\Exception $e) {
            Log::error("Failed to execute auto-reply", [
                'error' => $e->getMessage(),
                'engagement_id' => $engagement->id,
                'rule_id' => $rule->id
            ]);
            return false;
        }
    }

    private function replyToFacebook(Engagement $engagement, string $replyText, $account)
    {
        try {
            $payload = [
                'message' => $replyText,
            ];
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $account->access_token,
            ])->post("https://graph.facebook.com/v" . SettingsHelper::get('facebook_api_version', '18.0') . "/{$engagement->platform_engagement_id}/comments", $payload);

            if ($response->successful()) {
                $engagement->update(['is_automated_response' => true]);
                return true;
            } else {
                Log::error('Facebook reply failed', [
                    'response' => $response->json(),
                    'status_code' => $response->status(),
                    'engagement_id' => $engagement->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Facebook reply error', [
                'error' => $e->getMessage(),
                'engagement_id' => $engagement->id,
            ]);
        }

        return false;
    }

    private function replyToInstagram(Engagement $engagement, string $replyText, $account)
    {
        try {
            $payload = [
                'message' => $replyText,
            ];
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $account->access_token,
            ])->post("https://graph.instagram.com/v" . SettingsHelper::get('instagram_api_version', '18.0') . "/{$engagement->platform_engagement_id}/comments", $payload);

            if ($response->successful()) {
                $engagement->update(['is_automated_response' => true]);
                return true;
            } else {
                Log::error('Instagram reply failed', [
                    'response' => $response->json(),
                    'status_code' => $response->status(),
                    'engagement_id' => $engagement->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Instagram reply error', [
                'error' => $e->getMessage(),
                'engagement_id' => $engagement->id,
            ]);
        }

        return false;
    }

    private function replyToTwitter(Engagement $engagement, string $replyText, $account)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $account->access_token,
        ])->post("https://api.twitter.com/2/dm_conversations/with/{$engagement->actor_platform_id}/messages", [
            'text' => $replyText,
        ]);

        if ($response->successful()) {
            $engagement->update(['is_automated_response' => true]);
            return true;
        }

        return false;
    }

    private function executeTagLead(AutomationRule $rule, Engagement $engagement)
    {
        // Update engagement or create/update lead with tags
        $tags = $rule->action_parameters['tags'] ?? [];
        
        $lead = Lead::firstOrCreate([
            'social_account_id' => $engagement->social_account_id,
            'platform_lead_id' => $engagement->actor_platform_id,
        ], [
            'user_id' => $engagement->user_id,
            'first_name' => $engagement->actor_full_name ?: $engagement->actor_username,
            'platform' => $engagement->platform,
            'source_type' => 'engagement',
            'custom_fields' => ['tags' => $tags],
        ]);

        $lead->custom_fields = array_merge($lead->custom_fields ?: [], ['tags' => $tags]);
        $lead->save();

        return true;
    }

    private function executeForwardToCRM(AutomationRule $rule, Engagement $engagement)
    {
        // Forward engagement data to external CRM
        $crmEndpoint = $rule->action_parameters['crm_endpoint'] ?? null;
        $apiKey = $rule->action_parameters['api_key'] ?? null;

        if (!$crmEndpoint || !$apiKey) {
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post($crmEndpoint, [
                'engagement' => $engagement->toArray(),
                'account' => $engagement->socialAccount->toArray(),
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Failed to forward to CRM", [
                'error' => $e->getMessage(),
                'engagement_id' => $engagement->id
            ]);
            return false;
        }
    }

    private function executeAutoLike(AutomationRule $rule, Engagement $engagement)
    {
        try {
            $account = $engagement->socialAccount;
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $account->access_token,
            ])->post("https://graph.facebook.com/v" . SettingsHelper::get('facebook_api_version', '18.0') . "/{$engagement->platform_engagement_id}/likes");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Failed to execute auto-like", [
                'error' => $e->getMessage(),
                'engagement_id' => $engagement->id
            ]);
            return false;
        }
    }

    private function executeAutoDM(AutomationRule $rule, Engagement $engagement)
    {
        $messageText = $rule->action_parameters['message_text'] ?? '';
        $delay = $rule->action_parameters['delay'] ?? 0;

        if ($delay > 0) {
            sleep($delay);
        }

        try {
            $account = $engagement->socialAccount;
            
            // Send direct message based on platform
            switch ($account->platform) {
                case 'facebook':
                    return $this->sendFacebookDM($engagement, $messageText, $account);
                case 'instagram':
                    return $this->sendInstagramDM($engagement, $messageText, $account);
                case 'twitter':
                    return $this->sendTwitterDM($engagement, $messageText, $account);
                case 'x':
                    return $this->sendXDM($engagement, $messageText, $account);
                case 'tiktok':
                    return $this->sendTikTokDM($engagement, $messageText, $account);
                case 'youtube':
                    return $this->sendYouTubeDM($engagement, $messageText, $account);
                default:
                    return false;
            }
        } catch (\Exception $e) {
            Log::error("Failed to execute auto-DM", [
                'error' => $e->getMessage(),
                'engagement_id' => $engagement->id
            ]);
            return false;
        }
    }

    private function sendFacebookDM(Engagement $engagement, string $messageText, $account)
    {
        try {
            $payload = [
                'recipient' => ['id' => $engagement->actor_platform_id],
                'message' => ['text' => $messageText],
            ];
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $account->access_token,
            ])->post("https://graph.facebook.com/v" . SettingsHelper::get('facebook_api_version', '18.0') . "/me/messages", $payload);

            if ($response->successful()) {
                $engagement->update(['is_automated_response' => true]);
                return true;
            } else {
                Log::error('Facebook DM failed', [
                    'response' => $response->json(),
                    'status_code' => $response->status(),
                    'engagement_id' => $engagement->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Facebook DM error', [
                'error' => $e->getMessage(),
                'engagement_id' => $engagement->id,
            ]);
        }

        return false;
    }

    private function sendInstagramDM(Engagement $engagement, string $messageText, $account)
    {
        try {
            $payload = [
                'recipient' => ['id' => $engagement->actor_platform_id],
                'message' => ['text' => $messageText],
                'messaging_type' => 'MESSAGE_TAG',
                'tag' => 'ACCOUNT_UPDATE', // Appropriate tag for automated messages
            ];
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $account->access_token,
            ])->post("https://graph.facebook.com/v" . SettingsHelper::get('facebook_api_version', '18.0') . "/{$account->account_id}/messages", $payload);

            if ($response->successful()) {
                $engagement->update(['is_automated_response' => true]);
                return true;
            } else {
                Log::error('Instagram DM failed', [
                    'response' => $response->json(),
                    'status_code' => $response->status(),
                    'engagement_id' => $engagement->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Instagram DM error', [
                'error' => $e->getMessage(),
                'engagement_id' => $engagement->id,
            ]);
        }

        return false;
    }

    private function sendTwitterDM(Engagement $engagement, string $messageText, $account)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $account->access_token,
        ])->post("https://api.twitter.com/v" . SettingsHelper::get('twitter_api_version', '2') . "/dm_conversations/with/{$engagement->actor_platform_id}/messages", [
            'text' => $messageText,
        ]);

        return $response->successful();
    }

    private function replyToX(Engagement $engagement, string $replyText, $account)
    {
        try {
            // X API v2 doesn't support direct replies to tweets in the same way as other platforms
            // Instead, we can send a direct message or create a new tweet mentioning the user
            
            // Option 1: Reply to the original tweet
            $payload = [
                'text' => "@{$engagement->actor_username} {$replyText}",
                'reply' => [
                    'in_reply_to_tweet_id' => $engagement->platform_engagement_id,
                ],
            ];
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $account->access_token,
                'Content-Type' => 'application/json',
            ])->post("https://api.twitter.com/2/tweets", $payload);

            if ($response->successful()) {
                $engagement->update(['is_automated_response' => true]);
                return true;
            } else {
                Log::error('X reply failed', [
                    'response' => $response->json(),
                    'status_code' => $response->status(),
                    'engagement_id' => $engagement->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('X reply error', [
                'error' => $e->getMessage(),
                'engagement_id' => $engagement->id,
            ]);
        }

        return false;
    }

    private function replyToTikTok(Engagement $engagement, string $replyText, $account)
    {
        // TikTok doesn't have traditional comment replies in the same way
        // Implementation would depend on specific TikTok API capabilities
        // For now, log that this is not supported
        Log::info('TikTok reply not supported', ['engagement_id' => $engagement->id]);
        return false;
    }

    private function replyToYouTube(Engagement $engagement, string $replyText, $account)
    {
        try {
            $payload = [
                'snippet' => [
                    'parentId' => $engagement->platform_engagement_id,
                    'textOriginal' => $replyText,
                ],
            ];
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $account->access_token,
                'Content-Type' => 'application/json',
            ])->post("https://www.googleapis.com/youtube/v3/comments", $payload);

            if ($response->successful()) {
                $engagement->update(['is_automated_response' => true]);
                return true;
            } else {
                Log::error('YouTube reply failed', [
                    'response' => $response->json(),
                    'status_code' => $response->status(),
                    'engagement_id' => $engagement->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('YouTube reply error', [
                'error' => $e->getMessage(),
                'engagement_id' => $engagement->id,
            ]);
        }

        return false;
    }

    private function sendXDM(Engagement $engagement, string $messageText, $account)
    {
        try {
            // X Direct Message implementation using v2 API
            $payload = [
                'text' => $messageText,
            ];
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $account->access_token,
                'Content-Type' => 'application/json',
            ])->post("https://api.twitter.com/2/dm_conversations/with/{$engagement->actor_platform_id}/messages", $payload);

            if ($response->successful()) {
                $engagement->update(['is_automated_response' => true]);
                return true;
            } else {
                Log::error('X DM failed', [
                    'response' => $response->json(),
                    'status_code' => $response->status(),
                    'engagement_id' => $engagement->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('X DM error', [
                'error' => $e->getMessage(),
                'engagement_id' => $engagement->id,
            ]);
        }

        return false;
    }

    private function sendTikTokDM(Engagement $engagement, string $messageText, $account)
    {
        // TikTok DM implementation would require proper API access
        Log::info('TikTok DM not supported', ['engagement_id' => $engagement->id]);
        return false;
    }

    private function sendYouTubeDM(Engagement $engagement, string $messageText, $account)
    {
        // YouTube doesn't have traditional DMs like other platforms
        Log::info('YouTube DM not supported', ['engagement_id' => $engagement->id]);
        return false;
    }
}