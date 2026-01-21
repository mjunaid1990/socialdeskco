<?php

namespace App\Services;

use App\Models\Engagement;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DuplicateEngagementService
{
    public function isDuplicate(Engagement $engagement): bool
    {
        // Check for exact duplicate
        if ($this->isExactDuplicate($engagement)) {
            return true;
        }

        // Check for near duplicate
        if ($this->isNearDuplicate($engagement)) {
            return true;
        }

        // Check for cross-platform duplicate
        if ($this->isCrossPlatformDuplicate($engagement)) {
            return true;
        }

        return false;
    }

    private function isExactDuplicate(Engagement $engagement): bool
    {
        $cacheKey = "exact_duplicate_{$engagement->actor_platform_id}_{$engagement->platform_engagement_id}";
        
        return Cache::remember($cacheKey, 300, function () use ($engagement) {
            return Engagement::where('actor_platform_id', $engagement->actor_platform_id)
                ->where('platform_engagement_id', $engagement->platform_engagement_id)
                ->where('engagement_type', $engagement->engagement_type)
                ->where('platform', $engagement->platform)
                ->exists();
        });
    }

    private function isNearDuplicate(Engagement $engagement): bool
    {
        $content = $engagement->content ?? '';
        if (empty($content)) {
            return false;
        }

        $contentHash = md5(strtolower(trim($content)));
        $cacheKey = "near_duplicate_{$engagement->actor_platform_id}_{$contentHash}";
        
        return Cache::remember($cacheKey, 600, function () use ($engagement, $content) {
            // Check for identical content from same user within time window
            $identical = Engagement::where('actor_platform_id', $engagement->actor_platform_id)
                ->where('content', $content)
                ->where('engagement_type', $engagement->engagement_type)
                ->where('created_at', '>=', now()->subHours(24))
                ->first();

            if ($identical) {
                return true;
            }

            // Check for similar content using fuzzy matching
            $similarContent = Engagement::where('actor_platform_id', $engagement->actor_platform_id)
                ->where('engagement_type', $engagement->engagement_type)
                ->where('created_at', '>=', now()->subHours(6))
                ->whereNotNull('content')
                ->get();

            foreach ($similarContent as $existing) {
                if ($this->calculateSimilarity($content, $existing->content) > 0.85) {
                    return true;
                }
            }

            return false;
        });
    }

    private function isCrossPlatformDuplicate(Engagement $engagement): bool
    {
        $content = $engagement->content ?? '';
        if (empty($content)) {
            return false;
        }

        $cacheKey = "cross_duplicate_{$engagement->actor_username}_" . md5($content);
        
        return Cache::remember($cacheKey, 900, function () use ($engagement, $content) {
            // Check if same username posted similar content on other platforms
            $crossPlatform = Engagement::where('actor_username', $engagement->actor_username)
                ->where('platform', '!=', $engagement->platform)
                ->where('engagement_type', $engagement->engagement_type)
                ->where('created_at', '>=', now()->subHours(12))
                ->whereNotNull('content')
                ->get();

            foreach ($crossPlatform as $existing) {
                if ($this->calculateSimilarity($content, $existing->content) > 0.9) {
                    return true;
                }
            }

            return false;
        });
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

        // Use Levenshtein distance for similarity
        $distance = levenshtein($str1, $str2);
        $maxLen = max($len1, $len2);

        return 1.0 - ($distance / $maxLen);
    }

    public function markAsDuplicate(Engagement $engagement, string $originalEngagementId = null): void
    {
        $engagement->update([
            'is_duplicate' => true,
            'duplicate_of' => $originalEngagementId,
            'processed_at' => now(),
        ]);

        Log::info('Engagement marked as duplicate', [
            'engagement_id' => $engagement->id,
            'actor_username' => $engagement->actor_username,
            'content' => $engagement->content,
            'duplicate_of' => $originalEngagementId,
        ]);
    }

    public function findOriginalEngagement(Engagement $engagement): ?Engagement
    {
        // Find the earliest similar engagement
        $content = $engagement->content ?? '';
        if (empty($content)) {
            return null;
        }

        return Engagement::where('actor_platform_id', $engagement->actor_platform_id)
            ->where('engagement_type', $engagement->engagement_type)
            ->where('id', '!=', $engagement->id)
            ->whereNotNull('content')
            ->where('created_at', '<', $engagement->created_at)
            ->where(function ($query) use ($content) {
                $query->where('content', $content)
                      ->orWhereRaw('LEVENSHTEIN(LOWER(content), LOWER(?)) < ?', [$content, strlen($content) * 0.15]);
            })
            ->orderBy('created_at', 'asc')
            ->first();
    }

    public function getDuplicateStats(): array
    {
        $last24Hours = Engagement::where('created_at', '>=', now()->subDay())
            ->where('is_duplicate', true)
            ->count();

        $last7Days = Engagement::where('created_at', '>=', now()->subWeek())
            ->where('is_duplicate', true)
            ->count();

        $totalDuplicates = Engagement::where('is_duplicate', true)->count();
        $totalEngagements = Engagement::count();

        return [
            'duplicates_last_24h' => $last24Hours,
            'duplicates_last_7d' => $last7Days,
            'total_duplicates' => $totalDuplicates,
            'total_engagements' => $totalEngagements,
            'duplicate_rate' => $totalEngagements > 0 ? ($totalDuplicates / $totalEngagements) * 100 : 0,
        ];
    }

    public function cleanupDuplicates(): int
    {
        // Remove old duplicate records to free up space
        $deletedCount = Engagement::where('is_duplicate', true)
            ->where('created_at', '<', now()->subDays(30))
            ->delete();

        Log::info('Cleaned up old duplicate engagements', [
            'deleted_count' => $deletedCount,
        ]);

        return $deletedCount;
    }

    public function preventDuplicateProcessing(Engagement $engagement): bool
    {
        // Check if this engagement is already being processed
        $processingKey = "processing_engagement_{$engagement->id}";
        
        if (Cache::has($processingKey)) {
            return true; // Already being processed
        }

        // Mark as being processed for 5 minutes
        Cache::put($processingKey, true, 300);
        
        return false;
    }

    public function releaseProcessingLock(Engagement $engagement): void
    {
        $processingKey = "processing_engagement_{$engagement->id}";
        Cache::forget($processingKey);
    }
}
