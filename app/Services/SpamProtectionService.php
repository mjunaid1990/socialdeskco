<?php

namespace App\Services;

use App\Models\Engagement;
use App\Models\SpamFilter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SpamProtectionService
{
    private array $spamKeywords = [
        'click here', 'buy now', 'free money', 'guaranteed', 'act now',
        'limited time', 'special offer', 'congratulations', 'winner',
        'claim your', 'click below', 'urgent', 'immediate', 'exclusive',
        'risk free', 'no cost', '100% free', 'make money', 'work from home',
        'lose weight', 'viagra', 'casino', 'lottery', 'prize', 'award',
        'crypto', 'bitcoin', 'investment', 'trading', 'forex', 'binary',
        'mlm', 'multi level', 'pyramid', 'recruit', 'downline'
    ];

    private array $suspiciousPatterns = [
        '/\b\d{1,3}[-.\s]?\d{1,3}[-.\s]?\d{1,4}\b/', // Phone numbers
        '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', // Email addresses
        '/(?:https?:\/\/)?(?:www\.)?[a-zA-Z0-9-]+\.[a-zA-Z]{2,}(?:\/\S*)?/', // URLs
        '/\$\d+(?:,\d{3})*(?:\.\d{2})?/', // Money amounts
        '/\b\d{1,2}\/\d{1,2}\/\d{2,4}\b/', // Dates
        '/[!]{3,}/', // Multiple exclamation marks
        '/[?]{3,}/', // Multiple question marks
        '/[A-Z]{5,}/', // All caps words
    ];

    public function isSpam(Engagement $engagement): bool
    {
        $content = strtolower($engagement->content ?? '');
        $actorUsername = strtolower($engagement->actor_username ?? '');

        // Check against blacklist
        if ($this->isBlacklisted($engagement->actor_platform_id, $actorUsername)) {
            $this->logSpamDetection($engagement, 'blacklisted_user');
            return true;
        }

        // Check spam keywords
        if ($this->containsSpamKeywords($content)) {
            $this->logSpamDetection($engagement, 'spam_keywords');
            return true;
        }

        // Check suspicious patterns
        if ($this->hasSuspiciousPatterns($content)) {
            $this->logSpamDetection($engagement, 'suspicious_patterns');
            return true;
        }

        // Check engagement frequency
        if ($this->isSpamFrequency($engagement)) {
            $this->logSpamDetection($engagement, 'high_frequency');
            return true;
        }

        // Check duplicate content
        if ($this->isDuplicateContent($engagement)) {
            $this->logSpamDetection($engagement, 'duplicate_content');
            return true;
        }

        // Check bot-like behavior
        if ($this->isBotLikeBehavior($engagement)) {
            $this->logSpamDetection($engagement, 'bot_behavior');
            return true;
        }

        return false;
    }

    private function isBlacklisted(string $platformId, string $username): bool
    {
        $cacheKey = "blacklisted_users";
        $blacklisted = Cache::remember($cacheKey, 3600, function () {
            return SpamFilter::where('type', 'blacklisted_user')
                ->where('is_active', true)
                ->pluck('pattern')
                ->toArray();
        });

        return in_array($platformId, $blacklisted) || 
               in_array($username, $blacklisted);
    }

    private function containsSpamKeywords(string $content): bool
    {
        foreach ($this->spamKeywords as $keyword) {
            if (str_contains($content, $keyword)) {
                return true;
            }
        }

        // Check custom spam keywords from database
        $customKeywords = Cache::remember('custom_spam_keywords', 3600, function () {
            return SpamFilter::where('type', 'spam_keyword')
                ->where('is_active', true)
                ->pluck('pattern')
                ->toArray();
        });

        foreach ($customKeywords as $keyword) {
            if (str_contains($content, strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }

    private function hasSuspiciousPatterns(string $content): bool
    {
        foreach ($this->suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        // Check for excessive links
        $linkCount = substr_count($content, 'http') + substr_count($content, 'www.');
        if ($linkCount > 2) {
            return true;
        }

        // Check for excessive special characters
        $specialCharCount = preg_match_all('/[!@#$%^&*()_+\-=\[\]{};:"\\|,.<>\/?]/', $content);
        $totalLength = strlen($content);
        if ($totalLength > 0 && ($specialCharCount / $totalLength) > 0.3) {
            return true;
        }

        return false;
    }

    private function isSpamFrequency(Engagement $engagement): bool
    {
        $cacheKey = "user_engagement_freq_{$engagement->actor_platform_id}";
        
        return Cache::remember($cacheKey, 300, function () use ($engagement) {
            // Check if user has more than 10 engagements in the last minute
            $recentCount = Engagement::where('actor_platform_id', $engagement->actor_platform_id)
                ->where('created_at', '>=', now()->subMinute())
                ->count();

            if ($recentCount > 10) {
                return true;
            }

            // Check if user has more than 50 engagements in the last hour
            $hourlyCount = Engagement::where('actor_platform_id', $engagement->actor_platform_id)
                ->where('created_at', '>=', now()->subHour())
                ->count();

            if ($hourlyCount > 50) {
                return true;
            }

            return false;
        });
    }

    private function isDuplicateContent(Engagement $engagement): bool
    {
        $contentHash = md5(strtolower(trim($engagement->content ?? '')));
        
        // Check if this exact content was posted by the same user recently
        $duplicate = Engagement::where('actor_platform_id', $engagement->actor_platform_id)
            ->where('content_hash', $contentHash)
            ->where('created_at', '>=', now()->subHours(24))
            ->first();

        if ($duplicate) {
            return true;
        }

        // Check for near-duplicate content (90% similarity)
        $recentContent = Engagement::where('actor_platform_id', $engagement->actor_platform_id)
            ->where('created_at', '>=', now()->subHours(6))
            ->pluck('content')
            ->filter();

        foreach ($recentContent as $existingContent) {
            if ($this->calculateSimilarity($engagement->content ?? '', $existingContent) > 0.9) {
                return true;
            }
        }

        return false;
    }

    private function isBotLikeBehavior(Engagement $engagement): bool
    {
        // Check for generic usernames
        $username = $engagement->actor_username ?? '';
        if (preg_match('/^[a-z]+\d{4,}$/', strtolower($username))) {
            return true;
        }

        // Check for accounts with no profile picture (if platform data available)
        $platformData = $engagement->platform_data ?? [];
        if (isset($platformData['has_profile_picture']) && !$platformData['has_profile_picture']) {
            return true;
        }

        // Check for newly created accounts (if join date available)
        if (isset($platformData['account_created_at'])) {
            $accountAge = now()->diffInDays($platformData['account_created_at']);
            if ($accountAge < 7) {
                return true;
            }
        }

        // Check for accounts with very low follower counts (if available)
        if (isset($platformData['followers_count']) && $platformData['followers_count'] < 5) {
            return true;
        }

        return false;
    }

    private function calculateSimilarity(string $str1, string $str2): float
    {
        $str1 = strtolower(trim($str1));
        $str2 = strtolower(trim($str2));

        if ($str1 === $str2) {
            return 1.0;
        }

        $len1 = strlen($str1);
        $len2 = strlen($str2);

        if ($len1 === 0 || $len2 === 0) {
            return 0.0;
        }

        $distance = levenshtein($str1, $str2);
        $maxLen = max($len1, $len2);

        return 1.0 - ($distance / $maxLen);
    }

    private function logSpamDetection(Engagement $engagement, string $reason): void
    {
        Log::warning('Spam detected', [
            'engagement_id' => $engagement->id,
            'actor_username' => $engagement->actor_username,
            'actor_platform_id' => $engagement->actor_platform_id,
            'content' => $engagement->content,
            'reason' => $reason,
            'platform' => $engagement->platform,
        ]);

        // Update engagement to mark as spam
        $engagement->update([
            'is_spam' => true,
            'spam_reason' => $reason,
        ]);
    }

    public function addToBlacklist(string $platformId, string $username = null, string $reason = ''): void
    {
        SpamFilter::create([
            'type' => 'blacklisted_user',
            'pattern' => $platformId,
            'reason' => $reason,
            'is_active' => true,
        ]);

        if ($username) {
            SpamFilter::create([
                'type' => 'blacklisted_user',
                'pattern' => $username,
                'reason' => $reason,
                'is_active' => true,
            ]);
        }

        // Clear cache
        Cache::forget('blacklisted_users');
    }

    public function addSpamKeyword(string $keyword, string $reason = ''): void
    {
        SpamFilter::create([
            'type' => 'spam_keyword',
            'pattern' => $keyword,
            'reason' => $reason,
            'is_active' => true,
        ]);

        // Clear cache
        Cache::forget('custom_spam_keywords');
    }

    public function getSpamStats(): array
    {
        $last24Hours = Engagement::where('created_at', '>=', now()->subDay())
            ->where('is_spam', true)
            ->count();

        $last7Days = Engagement::where('created_at', '>=', now()->subWeek())
            ->where('is_spam', true)
            ->count();

        $totalSpam = Engagement::where('is_spam', true)->count();
        $totalEngagements = Engagement::count();

        return [
            'spam_last_24h' => $last24Hours,
            'spam_last_7d' => $last7Days,
            'total_spam' => $totalSpam,
            'total_engagements' => $totalEngagements,
            'spam_rate' => $totalEngagements > 0 ? ($totalSpam / $totalEngagements) * 100 : 0,
        ];
    }
}
