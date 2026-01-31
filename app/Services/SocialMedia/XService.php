<?php

namespace App\Services\SocialMedia;

use App\Models\Post;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class XService extends BaseSocialMediaService
{
    public function publishPost(Post $post, SocialAccount $account)
    {
        try {
            $payload = [
                'text' => $post->content,
            ];
            
            // Add media if available (requires media upload first)
            if ($post->media_files && !empty($post->media_files)) {
                // TODO: Implement media upload first, then add media_ids to payload
                // For now, we'll post without media
                Log::warning('X post includes media but media upload not implemented', [
                    'post_id' => $post->id,
                    'media_files' => $post->media_files
                ]);
            }
            
            // Make API request to X
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $account->access_token,
                'Content-Type' => 'application/json',
            ])->post('https://api.x.com/2/tweets', $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                
                // Update post with success data
                $post->update([
                    'platform_specific_data' => array_merge($post->platform_specific_data ?? [], [
                        'x' => $responseData
                    ]),
                    'status' => 'published',
                    'published_at' => now(),
                ]);
                
                Log::info('X post published successfully', [
                    'post_id' => $post->id,
                    'tweet_id' => $responseData['data']['id'] ?? null,
                    'response' => $responseData
                ]);
                
                return $responseData;
            } else {
                Log::error('X post failed', [
                    'post_id' => $post->id,
                    'status_code' => $response->status(),
                    'response' => $response->json(),
                    'account_id' => $account->id
                ]);
                
                $post->update([
                    'status' => 'failed',
                    'platform_specific_data' => array_merge($post->platform_specific_data ?? [], [
                        'x' => [
                            'error' => $response->json(),
                            'status_code' => $response->status()
                        ]
                    ])
                ]);
                
                return false;
            }
        } catch (\Exception $e) {
            Log::error('X API Error', [
                'error' => $e->getMessage(),
                'post_id' => $post->id,
                'account_id' => $account->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            $post->update([
                'status' => 'failed',
                'platform_specific_data' => array_merge($post->platform_specific_data ?? [], [
                    'x' => ['error' => $e->getMessage()]
                ])
            ]);
            
            return false;
        }
    }

    public function refreshToken(SocialAccount $account): bool
    {
        if (!$account->refresh_token) {
            return false;
        }

        try {
            $response = Http::asForm()->withBasicAuth(
                config('services.x.client_id'),
                config('services.x.client_secret')
            )->post('https://api.x.com/2/oauth2/token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $account->refresh_token,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $account->update([
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'] ?? $account->refresh_token,
                    'token_expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
                ]);
                
                Log::info('X token refreshed successfully', [
                    'account_id' => $account->id,
                    'expires_in' => $data['expires_in'] ?? null
                ]);
                
                return true;
            } else {
                Log::error('X token refresh failed', [
                    'status_code' => $response->status(),
                    'response' => $response->json(),
                    'account_id' => $account->id,
                ]);
                
                return false;
            }
        } catch (\Exception $e) {
            Log::error('X token refresh exception', [
                'error' => $e->getMessage(),
                'account_id' => $account->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }

    public function getPlatformName(): string
    {
        return 'x';
    }
}
