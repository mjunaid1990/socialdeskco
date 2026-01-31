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
            
            // Handle media upload if available
            if ($post->media_files && !empty($post->media_files)) {
                $mediaIds = [];
                
                foreach ($post->media_files as $mediaFile) {
                    $mediaId = $this->uploadMedia($mediaFile, $account);
                    if ($mediaId) {
                        $mediaIds[] = $mediaId;
                    }
                }
                
                if (!empty($mediaIds)) {
                    $payload['media'] = [
                        'media_ids' => $mediaIds
                    ];
                }
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

    public function deletePost(Post $post, SocialAccount $account): bool
    {
        try {
            // Get tweet ID from platform specific data
            $platformData = $post->platform_specific_data['x'] ?? [];
            $tweetId = $platformData['data']['id'] ?? null;
            
            if (!$tweetId) {
                Log::error('Cannot delete X post: No tweet ID', [
                    'post_id' => $post->id,
                    'platform_data' => $platformData,
                    'error' => 'Tweet ID is required for deletion'
                ]);
                return false;
            }

            // Delete the tweet using X API v2
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $account->access_token,
            ])->delete("https://api.x.com/2/tweets/{$tweetId}");
            
            if ($response->successful()) {
                $responseData = $response->json();
                
                // X returns {"data": {"deleted": true}} on successful deletion
                if ($responseData['data']['deleted'] ?? false) {
                    $this->updatePostStatus($post, 'deleted', $responseData);
                    Log::info('X post deleted successfully', [
                        'post_id' => $post->id,
                        'tweet_id' => $tweetId,
                        'response' => $responseData
                    ]);
                    return true;
                } else {
                    Log::error('X post deletion failed', [
                        'post_id' => $post->id,
                        'tweet_id' => $tweetId,
                        'response_body' => $responseData
                    ]);
                    return false;
                }
            } else {
                Log::error('X post deletion request failed', [
                    'post_id' => $post->id,
                    'tweet_id' => $tweetId,
                    'status_code' => $response->status(),
                    'response' => $response->json()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('X post deletion exception', [
                'error' => $e->getMessage(),
                'post_id' => $post->id,
                'account_id' => $account->id,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    private function uploadMedia(string $mediaFile, SocialAccount $account): ?string
    {
        try {
            // Determine media type based on file extension
            $mediaType = $this->getMediaType($mediaFile);
            
            // Step 1: Initialize upload
            $initResponse = Http::asForm()->withHeaders([
                'Authorization' => 'Bearer ' . $account->access_token,
            ])->post('https://api.x.com/2/media/upload', [
                'command' => 'INIT',
                'media_type' => $mediaType,
                'total_bytes' => filesize($mediaFile),
                'media_category' => $this->getMediaCategory($mediaType),
            ]);

            if (!$initResponse->successful()) {
                Log::error('X media upload initialization failed', [
                    'response' => $initResponse->json(),
                    'status_code' => $initResponse->status()
                ]);
                return null;
            }

            $initData = $initResponse->json();
            $mediaId = $initData['media_id_string'] ?? null;

            if (!$mediaId) {
                Log::error('X media upload: No media ID received');
                return null;
            }

            // Step 2: Append media data (simplified for single file upload)
            $appendResponse = Http::asMultipart()->withHeaders([
                'Authorization' => 'Bearer ' . $account->access_token,
            ])->post('https://api.x.com/2/media/upload', [
                'command' => 'APPEND',
                'media_id' => $mediaId,
                'segment_index' => 0,
                'media' => fopen($mediaFile, 'r'),
            ]);

            if (!$appendResponse->successful()) {
                Log::error('X media upload append failed', [
                    'response' => $appendResponse->json(),
                    'status_code' => $appendResponse->status(),
                    'media_id' => $mediaId
                ]);
                return null;
            }

            // Step 3: Finalize upload
            $finalizeResponse = Http::asForm()->withHeaders([
                'Authorization' => 'Bearer ' . $account->access_token,
            ])->post('https://api.x.com/2/media/upload', [
                'command' => 'FINALIZE',
                'media_id' => $mediaId,
            ]);

            if ($finalizeResponse->successful()) {
                Log::info('X media uploaded successfully', [
                    'media_id' => $mediaId,
                    'media_type' => $mediaType
                ]);
                return $mediaId;
            } else {
                Log::error('X media upload finalization failed', [
                    'response' => $finalizeResponse->json(),
                    'status_code' => $finalizeResponse->status(),
                    'media_id' => $mediaId
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('X media upload exception', [
                'error' => $e->getMessage(),
                'media_file' => $mediaFile,
                'account_id' => $account->id,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    private function getMediaType(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        return match($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'mp4' => 'video/mp4',
            'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo',
            default => 'image/jpeg',
        };
    }

    private function getMediaCategory(string $mediaType): string
    {
        return str_starts_with($mediaType, 'video/') ? 'tweet_video' : 'tweet_image';
    }

    private function updatePostStatus(Post $post, string $status, array $platformData = []): void
    {
        $currentPlatformData = $post->platform_specific_data ?? [];
        $post->platform_specific_data = array_merge($currentPlatformData, $platformData);
        $post->status = $status;
        $post->save();
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
