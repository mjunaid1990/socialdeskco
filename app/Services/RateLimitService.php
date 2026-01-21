<?php

namespace App\Services;

use App\Models\AutomationRule;
use App\Models\Engagement;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RateLimitService
{
    private array $defaultLimits = [
        'per_minute' => 10,
        'per_hour' => 100,
        'per_day' => 500,
    ];

    private array $platformLimits = [
        'twitter' => [
            'per_minute' => 5,
            'per_hour' => 50,
            'per_day' => 200,
        ],
        'facebook' => [
            'per_minute' => 8,
            'per_hour' => 80,
            'per_day' => 400,
        ],
        'instagram' => [
            'per_minute' => 6,
            'per_hour' => 60,
            'per_day' => 300,
        ],
        'tiktok' => [
            'per_minute' => 3,
            'per_hour' => 30,
            'per_day' => 150,
        ],
        'youtube' => [
            'per_minute' => 4,
            'per_hour' => 40,
            'per_day' => 200,
        ],
    ];

    public function checkRateLimit(string $platform, string $actionType, string $identifier = null): bool
    {
        $limits = $this->platformLimits[$platform] ?? $this->defaultLimits;
        $identifier = $identifier ?? 'global';

        // Check per-minute limit
        if (!$this->checkWindowLimit($platform, $actionType, $identifier, 'minute', $limits['per_minute'])) {
            return false;
        }

        // Check per-hour limit
        if (!$this->checkWindowLimit($platform, $actionType, $identifier, 'hour', $limits['per_hour'])) {
            return false;
        }

        // Check per-day limit
        if (!$this->checkWindowLimit($platform, $actionType, $identifier, 'day', $limits['per_day'])) {
            return false;
        }

        return true;
    }

    private function checkWindowLimit(string $platform, string $actionType, string $identifier, string $window, int $limit): bool
    {
        $cacheKey = "rate_limit_{$platform}_{$actionType}_{$identifier}_{$window}";
        $current = Cache::get($cacheKey, 0);

        if ($current >= $limit) {
            Log::warning('Rate limit exceeded', [
                'platform' => $platform,
                'action_type' => $actionType,
                'identifier' => $identifier,
                'window' => $window,
                'current' => $current,
                'limit' => $limit,
            ]);
            return false;
        }

        // Increment counter
        Cache::increment($cacheKey);
        
        // Set expiration if this is the first hit
        if ($current === 0) {
            $ttl = match($window) {
                'minute' => 60,
                'hour' => 3600,
                'day' => 86400,
                default => 60,
            };
            Cache::expire($cacheKey, $ttl);
        }

        return true;
    }

    public function checkAutomationRuleLimit(AutomationRule $rule): bool
    {
        $cacheKey = "automation_rule_limit_{$rule->id}";
        $current = Cache::get($cacheKey, 0);

        // Default rule limit: 100 executions per hour
        $limit = $rule->action_parameters['rate_limit'] ?? 100;
        $window = $rule->action_parameters['rate_window'] ?? 'hour';

        if ($current >= $limit) {
            Log::warning('Automation rule rate limit exceeded', [
                'rule_id' => $rule->id,
                'rule_name' => $rule->name,
                'current' => $current,
                'limit' => $limit,
                'window' => $window,
            ]);
            return false;
        }

        // Increment counter
        Cache::increment($cacheKey);
        
        // Set expiration if this is the first hit
        if ($current === 0) {
            $ttl = match($window) {
                'minute' => 60,
                'hour' => 3600,
                'day' => 86400,
                default => 3600,
            };
            Cache::expire($cacheKey, $ttl);
        }

        return true;
    }

    public function checkUserEngagementLimit(int $userId, string $platform): bool
    {
        $cacheKey = "user_engagement_limit_{$userId}_{$platform}";
        $current = Cache::get($cacheKey, 0);

        // Default user limit: 1000 engagements per day per platform
        $limit = 1000;

        if ($current >= $limit) {
            Log::warning('User engagement limit exceeded', [
                'user_id' => $userId,
                'platform' => $platform,
                'current' => $current,
                'limit' => $limit,
            ]);
            return false;
        }

        // Increment counter
        Cache::increment($cacheKey);
        
        // Set expiration if this is the first hit
        if ($current === 0) {
            Cache::expire($cacheKey, 86400); // 24 hours
        }

        return true;
    }

    public function checkCooldownPeriod(Engagement $engagement, string $actionType): bool
    {
        $cacheKey = "cooldown_{$engagement->actor_platform_id}_{$actionType}";
        $lastAction = Cache::get($cacheKey);

        if ($lastAction) {
            $cooldownMinutes = match($actionType) {
                'auto_reply' => 5,
                'auto_dm' => 30,
                'auto_like' => 1,
                'tag_lead' => 60,
                default => 10,
            };

            $cooldownEnd = $lastAction->addMinutes($cooldownMinutes);
            
            if (now()->lt($cooldownEnd)) {
                Log::info('Action in cooldown period', [
                    'actor_platform_id' => $engagement->actor_platform_id,
                    'action_type' => $actionType,
                    'cooldown_end' => $cooldownEnd,
                ]);
                return false;
            }
        }

        // Update last action timestamp
        Cache::put($cacheKey, now(), 3600); // Cache for 1 hour

        return true;
    }

    public function getRateLimitStats(): array
    {
        $stats = [];

        foreach (array_keys($this->platformLimits) as $platform) {
            $stats[$platform] = [
                'minute_usage' => $this->getCurrentUsage($platform, 'auto_reply', 'global', 'minute'),
                'hour_usage' => $this->getCurrentUsage($platform, 'auto_reply', 'global', 'hour'),
                'day_usage' => $this->getCurrentUsage($platform, 'auto_reply', 'global', 'day'),
                'limits' => $this->platformLimits[$platform],
            ];
        }

        return $stats;
    }

    private function getCurrentUsage(string $platform, string $actionType, string $identifier, string $window): int
    {
        $cacheKey = "rate_limit_{$platform}_{$actionType}_{$identifier}_{$window}";
        return Cache::get($cacheKey, 0);
    }

    public function resetRateLimit(string $platform, string $actionType, string $identifier = null, string $window = null): void
    {
        $identifier = $identifier ?? 'global';
        
        if ($window) {
            $cacheKey = "rate_limit_{$platform}_{$actionType}_{$identifier}_{$window}";
            Cache::forget($cacheKey);
        } else {
            // Reset all windows for this combination
            foreach (['minute', 'hour', 'day'] as $w) {
                $cacheKey = "rate_limit_{$platform}_{$actionType}_{$identifier}_{$w}";
                Cache::forget($cacheKey);
            }
        }

        Log::info('Rate limit reset', [
            'platform' => $platform,
            'action_type' => $actionType,
            'identifier' => $identifier,
            'window' => $window,
        ]);
    }

    public function getRemainingQuota(string $platform, string $actionType, string $identifier = null): array
    {
        $identifier = $identifier ?? 'global';
        $limits = $this->platformLimits[$platform] ?? $this->defaultLimits;

        return [
            'minute_remaining' => max(0, $limits['per_minute'] - $this->getCurrentUsage($platform, $actionType, $identifier, 'minute')),
            'hour_remaining' => max(0, $limits['per_hour'] - $this->getCurrentUsage($platform, $actionType, $identifier, 'hour')),
            'day_remaining' => max(0, $limits['per_day'] - $this->getCurrentUsage($platform, $actionType, $identifier, 'day')),
        ];
    }

    public function isNearLimit(string $platform, string $actionType, string $identifier = null, float $threshold = 0.8): bool
    {
        $remaining = $this->getRemainingQuota($platform, $actionType, $identifier);
        $limits = $this->platformLimits[$platform] ?? $this->defaultLimits;

        // Check if any window is near its limit
        if ($limits['per_minute'] > 0 && ($remaining['minute_remaining'] / $limits['per_minute']) < (1 - $threshold)) {
            return true;
        }

        if ($limits['per_hour'] > 0 && ($remaining['hour_remaining'] / $limits['per_hour']) < (1 - $threshold)) {
            return true;
        }

        if ($limits['per_day'] > 0 && ($remaining['day_remaining'] / $limits['per_day']) < (1 - $threshold)) {
            return true;
        }

        return false;
    }
}
