<?php

namespace App\Http\Middleware;

use App\Services\SpamProtectionService;
use App\Services\DuplicateEngagementService;
use App\Services\RateLimitService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SpamProtectionMiddleware
{
    public function __construct(
        private SpamProtectionService $spamProtection,
        private DuplicateEngagementService $duplicateService,
        private RateLimitService $rateLimit
    ) {}

    public function handle(Request $request, Closure $next)
    {
        // Only apply to engagement-related routes
        if (!$this->isEngagementRoute($request)) {
            return $next($request);
        }

        try {
            // Get engagement data from request
            $engagementData = $this->extractEngagementData($request);
            
            if (!$engagementData) {
                return $next($request);
            }

            // Check rate limits first
            if (!$this->checkRateLimits($engagementData)) {
                return response()->json([
                    'error' => 'Rate limit exceeded',
                    'message' => 'Too many requests. Please try again later.',
                ], 429);
            }

            // Check for spam
            if ($this->isSpam($engagementData)) {
                return response()->json([
                    'error' => 'Spam detected',
                    'message' => 'Your content has been flagged as spam.',
                ], 403);
            }

            // Check for duplicates
            if ($this->isDuplicate($engagementData)) {
                return response()->json([
                    'error' => 'Duplicate content',
                    'message' => 'This content has already been posted.',
                ], 409);
            }

        } catch (\Exception $e) {
            Log::error('Spam protection middleware error', [
                'error' => $e->getMessage(),
                'request_path' => $request->path(),
            ]);
            
            // Continue processing if spam protection fails
            // This prevents the middleware from breaking the application
        }

        return $next($request);
    }

    private function isEngagementRoute(Request $request): bool
    {
        $path = $request->path();
        
        return str_contains($path, 'engagements') ||
               str_contains($path, 'automation') ||
               str_contains($path, 'comments') ||
               str_contains($path, 'messages');
    }

    private function extractEngagementData(Request $request): ?array
    {
        // Try to get data from different request formats
        $data = null;

        if ($request->isJson()) {
            $data = $request->json()->all();
        } elseif ($request->request->count() > 0) {
            $data = $request->request->all();
        }

        if (!$data) {
            return null;
        }

        // Normalize engagement data
        return [
            'content' => $data['content'] ?? $data['message'] ?? $data['text'] ?? null,
            'actor_username' => $data['actor_username'] ?? $data['username'] ?? null,
            'actor_platform_id' => $data['actor_platform_id'] ?? $data['user_id'] ?? null,
            'platform' => $data['platform'] ?? null,
            'engagement_type' => $data['engagement_type'] ?? $data['type'] ?? 'comment',
        ];
    }

    private function checkRateLimits(array $engagementData): bool
    {
        $platform = $engagementData['platform'] ?? 'unknown';
        $actionType = $engagementData['engagement_type'] ?? 'comment';
        $identifier = $engagementData['actor_platform_id'] ?? 'anonymous';

        return $this->rateLimit->checkRateLimit($platform, $actionType, $identifier);
    }

    private function isSpam(array $engagementData): bool
    {
        // Create a temporary engagement object for spam checking
        $tempEngagement = new class {
            public $content;
            public $actor_username;
            public $actor_platform_id;
            public $platform;
            public $platform_data;
        };

        $tempEngagement->content = $engagementData['content'];
        $tempEngagement->actor_username = $engagementData['actor_username'];
        $tempEngagement->actor_platform_id = $engagementData['actor_platform_id'];
        $tempEngagement->platform = $engagementData['platform'];
        $tempEngagement->platform_data = $engagementData['platform_data'] ?? [];

        return $this->spamProtection->isSpam($tempEngagement);
    }

    private function isDuplicate(array $engagementData): bool
    {
        // Create a temporary engagement object for duplicate checking
        $tempEngagement = new class {
            public $content;
            public $actor_username;
            public $actor_platform_id;
            public $platform;
            public $engagement_type;
            public $created_at;
        };

        $tempEngagement->content = $engagementData['content'];
        $tempEngagement->actor_username = $engagementData['actor_username'];
        $tempEngagement->actor_platform_id = $engagementData['actor_platform_id'];
        $tempEngagement->platform = $engagementData['platform'];
        $tempEngagement->engagement_type = $engagementData['engagement_type'];
        $tempEngagement->created_at = now();

        return $this->duplicateService->isDuplicate($tempEngagement);
    }
}
