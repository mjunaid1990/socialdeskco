<?php

namespace App\Services\SocialMedia;

use App\Models\Post;
use App\Models\SocialAccount;
use App\Helpers\SettingsHelper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TikTokService extends BaseSocialMediaService
{
    public function publishPost(Post $post, SocialAccount $account)
    {
        try {
            Log::info('TikTokService: Starting publishPost for post ID: ' . $post->id);

            // Check if we have media files to post
            $hasMedia = $post->media_files && !empty($post->media_files);
            $mediaType = $this->getMediaType($post);
            
            if ($hasMedia) {
                if ($mediaType === 'video') {
                    return $this->publishVideo($post, $account);
                } else {
                    return $this->publishPhoto($post, $account);
                }
            } else {
                // For text-only posts, we can still publish but TikTok is primarily visual
                return $this->publishText($post, $account);
            }
        } catch (\Exception $e) {
            return $this->handleException($post, 'TikTok', $e);
        }
    }

    private function publishVideo(Post $post, SocialAccount $account)
    {
        try {
            $mediaUrl = $post->media_files[0];
            
            // Initialize video upload
            $initResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $account->access_token,
                'Content-Type' => 'application/json',
            ])->post("https://open.tiktokapis.com/v2/post/publish/video/init/", [
                'post_info' => [
                    'title' => $post->content,
                    'privacy_level' => 'PUBLIC_TO_EVERYONE',
                    'disable_duet' => false,
                    'disable_comment' => false,
                    'disable_stitch' => false,
                ],
                'source_info' => [
                    'source' => 'PULL_FROM_URL',
                    'video_url' => $mediaUrl,
                ]
            ]);

            if ($initResponse->successful()) {
                $responseData = $initResponse->json();
                $publishId = $responseData['data']['publish_id'] ?? null;
                
                if ($publishId) {
                    // Store the publish ID for status checking
                    $platformData = [
                        'publish_id' => $publishId,
                        'status' => 'created',
                    ];
                    
                    $post->update([
                        'platform_specific_data' => $platformData,
                        'status' => 'processing',
                    ]);
                    
                    // Optionally check status periodically
                    return $this->checkPublishStatus($post, $account, $publishId);
                }
            }
            
            return $this->handleApiError($post, 'TikTok video init', $initResponse, [], $account);
        } catch (\Exception $e) {
            return $this->handleException($post, 'TikTok video', $e);
        }
    }
    
    private function publishPhoto(Post $post, SocialAccount $account)
    {
        try {
            // For photo posts, use the content posting API
            $photoUrls = $post->media_files;
            
            $initResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $account->access_token,
                'Content-Type' => 'application/json',
            ])->post("https://open.tiktokapis.com/v2/post/publish/content/init/", [
                'post_info' => [
                    'title' => $post->content,
                    'privacy_level' => 'PUBLIC_TO_EVERYONE',
                    'disable_comment' => false,
                    'auto_add_music' => false,
                ],
                'source_info' => [
                    'source' => 'PULL_FROM_URL',
                    'photo_images' => $photoUrls,
                ],
                'post_mode' => 'DIRECT_POST',
                'media_type' => 'PHOTO'
            ]);

            if ($initResponse->successful()) {
                $responseData = $initResponse->json();
                $publishId = $responseData['data']['publish_id'] ?? null;
                
                if ($publishId) {
                    // Store the publish ID for status checking
                    $platformData = [
                        'publish_id' => $publishId,
                        'status' => 'created',
                    ];
                    
                    $post->update([
                        'platform_specific_data' => $platformData,
                        'status' => 'processing',
                    ]);
                    
                    return $this->checkPublishStatus($post, $account, $publishId);
                }
            }
            
            return $this->handleApiError($post, 'TikTok photo init', $initResponse, [], $account);
        } catch (\Exception $e) {
            return $this->handleException($post, 'TikTok photo', $e);
        }
    }

    private function publishText(Post $post, SocialAccount $account)
    {
        // For text-only posts, we'll use the content API with minimal privacy settings
        $initResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $account->access_token,
            'Content-Type' => 'application/json',
        ])->post("https://open.tiktokapis.com/v2/post/publish/content/init/", [
            'post_info' => [
                'title' => $post->content,
                'privacy_level' => 'PUBLIC_TO_EVERYONE',
                'disable_comment' => false,
                'auto_add_music' => false,
            ],
            'source_info' => [
                'source' => 'PULL_FROM_URL',
                'photo_images' => [], // No images for text post
            ],
            'post_mode' => 'DIRECT_POST',
            'media_type' => 'TEXT' // Note: TEXT is not officially supported, using PHOTO with empty images
        ]);

        if ($initResponse->successful()) {
            $responseData = $initResponse->json();
            $publishId = $responseData['data']['publish_id'] ?? null;
            
            if ($publishId) {
                // Store the publish ID for status checking
                $platformData = [
                    'publish_id' => $publishId,
                    'status' => 'created',
                ];
                
                $post->update([
                    'platform_specific_data' => $platformData,
                    'status' => 'processing',
                ]);
                
                return $this->checkPublishStatus($post, $account, $publishId);
            }
        }
        
        return $this->handleApiError($post, 'TikTok text init', $initResponse, [], $account);
    }
    
    private function checkPublishStatus(Post $post, SocialAccount $account, string $publishId)
    {
        // Check the status of the post using the publish ID
        $statusResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $account->access_token,
            'Content-Type' => 'application/json',
        ])->post("https://open.tiktokapis.com/v2/post/publish/status/fetch/", [
            'publish_id' => $publishId
        ]);
        
        if ($statusResponse->successful()) {
            $responseData = $statusResponse->json();
            $status = $responseData['data']['status'] ?? 'unknown';
            
            // Update the post status based on the API response
            if ($status === 'SUCCESS') {
                $platformData = array_merge($post->platform_specific_data ?? [], [
                    'publish_id' => $publishId,
                    'status' => 'published',
                    'result' => $responseData['data'],
                ]);
                
                $post->update([
                    'platform_specific_data' => $platformData,
                    'status' => 'published',
                    'published_at' => now(),
                ]);
                
                return true;
            } else {
                // Update status but don't mark as failed immediately, as it might still be processing
                $platformData = array_merge($post->platform_specific_data ?? [], [
                    'publish_id' => $publishId,
                    'status' => $status,
                    'result' => $responseData['data'],
                ]);
                
                $post->update([
                    'platform_specific_data' => $platformData,
                    'status' => $status === 'FAIL' ? 'failed' : 'processing',
                ]);
                
                return $status === 'SUCCESS';
            }
        } else {
            return $this->handleApiError($post, 'TikTok status check', $statusResponse, ['publish_id' => $publishId], $account);
        }
    }
    
    private function getMediaType(Post $post): string
    {
        if ($post->media_files && !empty($post->media_files[0])) {
            $url = $post->media_files[0];
            $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
            $videoExtensions = ['mp4', 'mov', 'avi', 'wmv', 'flv', 'webm', 'mkv'];
            
            return in_array($extension, $videoExtensions) ? 'video' : 'photo';
        }
        
        return 'text';
    }

    public function deletePost(Post $post, SocialAccount $account)
    {
        // TikTok API doesn't typically allow deleting posts via API
        // This is a limitation of TikTok's Content API
        Log::info('TikTokService: Delete post not supported via API', [
            'post_id' => $post->id
        ]);

        return false;
    }

    public function refreshToken(SocialAccount $account): bool
    {
        if (!$this->getRefreshToken($account)) {
            return false;
        }
        
        try {
            $response = Http::asForm()->post('https://open.tiktokapis.com/v2/oauth/token/', [
                'client_key' => $this->getClientId('tiktok'),
                'client_secret' => $this->getClientSecret('tiktok'),
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->getRefreshToken($account),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->updateAccountTokens($account, $data);
                return true;
            } else {
                $this->logApiError('TikTok token refresh failed', [
                    'status_code' => $response->status(),
                    'response' => $response->json(),
                    'account_id' => $account->id,
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('TikTok token refresh exception', [
                'error' => $e->getMessage(),
                'account_id' => $account->id,
            ]);
            return false;
        }
    }

    public function getPlatformName(): string
    {
        return 'tiktok';
    }
}