<?php

namespace App\Services;

use App\Models\SocialAccount;
use App\Models\Post;
use App\Services\SocialMedia\SocialMediaServiceFactory;
use Illuminate\Support\Facades\Log;

class SocialMediaService
{
    public function publishPost(Post $post)
    {
        Log::info('SocialMediaService: Starting publishPost for post ID: ' . $post->id);
        
        // Get all social accounts for this post
        $socialAccounts = $post->socialAccounts;
        
        if ($socialAccounts->isEmpty()) {
            Log::error('SocialMediaService: No social accounts found for post ID: ' . $post->id);
            $post->update([
                'status' => 'failed',
                'platform_specific_data' => ['error' => 'No social accounts found']
            ]);
            return false;
        }
        
        Log::info('SocialMediaService: Found ' . $socialAccounts->count() . ' social accounts for post ID: ' . $post->id);
        
        $results = [];
        $successCount = 0;
        $failureCount = 0;
        
        foreach ($socialAccounts as $account) {
            Log::info('SocialMediaService: Processing account for platform: ' . $account->platform . ' (ID: ' . $account->id . ')');
            
            try {
                $platformService = SocialMediaServiceFactory::createForAccount($account);
                Log::info('SocialMediaService: Created platform service for: ' . $account->platform);
                
                $result = $platformService->publishPost($post, $account);
                $results[$account->id] = $result;
                
                // Handle different return types from platform services
                if (is_string($result)) {
                    // Platform service returned a video ID or other identifier
                    $successCount++;
                    Log::info('SocialMediaService: Successfully published to ' . $account->platform . ' (ID: ' . $account->id . ') with identifier: ' . $result);
                } elseif ($result === true) {
                    // Platform service returned true for success
                    $successCount++;
                    Log::info('SocialMediaService: Successfully published to ' . $account->platform . ' (ID: ' . $account->id . ')');
                } else {
                    // Platform service returned false for failure
                    $failureCount++;
                    Log::warning('SocialMediaService: Failed to publish to ' . $account->platform . ' (ID: ' . $account->id . ')');
                }
                
            } catch (\Exception $e) {
                $failureCount++;
                $results[$account->id] = false;
                
                Log::error('SocialMediaService Error', [
                    'error' => $e->getMessage(),
                    'platform' => $account->platform,
                    'account_id' => $account->id,
                    'post_id' => $post->id,
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        
        // Update post status based on results
        if ($successCount > 0 && $failureCount === 0) {
            // All successful
            $post->update([
                'status' => 'published',
                'published_at' => now(),
                'platform_specific_data' => [
                    'results' => $results,
                    'success_count' => $successCount,
                    'failure_count' => $failureCount,
                    'published_at' => now()->toISOString()
                ]
            ]);
            
            Log::info('SocialMediaService: All accounts published successfully for post ID: ' . $post->id);
            return true;
            
        } elseif ($successCount > 0 && $failureCount > 0) {
            // Partial success
            $post->update([
                'status' => 'published', // Still mark as published since some worked
                'published_at' => now(),
                'platform_specific_data' => [
                    'results' => $results,
                    'success_count' => $successCount,
                    'failure_count' => $failureCount,
                    'published_at' => now()->toISOString(),
                    'partial_success' => true
                ]
            ]);
            
            Log::info('SocialMediaService: Partial success for post ID: ' . $post->id . ' (' . $successCount . ' successful, ' . $failureCount . ' failed)');
            return true;
            
        } else {
            // All failed
            $post->update([
                'status' => 'failed',
                'platform_specific_data' => [
                    'results' => $results,
                    'success_count' => $successCount,
                    'failure_count' => $failureCount,
                    'error' => 'All social accounts failed to publish'
                ]
            ]);
            
            Log::error('SocialMediaService: All accounts failed to publish for post ID: ' . $post->id);
            return false;
        }
    }

    public function deletePost(Post $post, SocialAccount $account)
    {
        Log::info('SocialMediaService: Starting deletePost for post ID: ' . $post->id);
        
        try {
            $platformService = SocialMediaServiceFactory::createForAccount($account);
            Log::info('SocialMediaService: Created platform service for: ' . $account->platform);
            
            $result = $platformService->deletePost($post, $account);
            Log::info('SocialMediaService: Platform service deletePost returned: ' . ($result ? 'true' : 'false'));
            
            return $result;
        } catch (\Exception $e) {
            Log::error('SocialMediaService deletePost Error', [
                'error' => $e->getMessage(),
                'platform' => $account->platform,
                'post_id' => $post->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }

    private function publishToFacebook(Post $post, SocialAccount $account)
    {
        try {
            Log::info('SocialMediaService: Starting publishToFacebook for post ID: ' . $post->id);
            
            $payload = [
                'message' => $post->content,
            ];
            
            // Add media if available
            if ($post->media_files && !empty($post->media_files)) {
                // For Facebook, we may need to upload the media first and then reference it
                $payload['url'] = $post->media_files[0];
            }
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $account->access_token,
            ])->post("https://graph.facebook.com/v" . SettingsHelper::get('facebook_api_version', '18.0') . "/{$account->account_id}/feed", $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                $post->update([
                    'platform_specific_data' => $responseData,
                    'status' => 'published',
                    'published_at' => now(),
                ]);
                
                return $responseData;
            } else {
                Log::error('Facebook post failed', [
                    'response' => $response->json(),
                    'status_code' => $response->status(),
                    'post_id' => $post->id
                ]);
                
                $post->update([
                    'status' => 'failed',
                    'platform_specific_data' => $response->json()
                ]);
                
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Facebook API Error', [
                'error' => $e->getMessage(),
                'post_id' => $post->id
            ]);
            
            $post->update([
                'status' => 'failed',
                'platform_specific_data' => ['error' => $e->getMessage()]
            ]);
            
            return false;
        }
    }

    private function publishToInstagram(Post $post, SocialAccount $account)
    {
        try {
            // For Instagram, we need to create a media object first
            $imageUrl = $post->media_files ? $post->media_files[0] : null;
            
            $payload = [
                'caption' => $post->content,
            ];
            
            // Add image URL if available
            if ($imageUrl) {
                $payload['image_url'] = $imageUrl;
            }
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $account->access_token,
            ])->post("https://graph.instagram.com/v" . SettingsHelper::get('instagram_api_version', '18.0') . "/me/media", $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                $creationId = $responseData['id'] ?? null;
                
                if ($creationId) {
                    // Then publish the media
                    $publishResponse = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $account->access_token,
                    ])->post("https://graph.instagram.com/v" . SettingsHelper::get('instagram_api_version', '18.0') . "/me/media_publish", [
                        'creation_id' => $creationId,
                    ]);

                    if ($publishResponse->successful()) {
                        $publishData = $publishResponse->json();
                        $post->update([
                            'platform_specific_data' => array_merge($responseData, $publishData),
                            'status' => 'published',
                            'published_at' => now(),
                        ]);
                        
                        return $publishData;
                    } else {
                        Log::error('Instagram publish failed', [
                            'response' => $publishResponse->json(),
                            'status_code' => $publishResponse->status(),
                            'post_id' => $post->id
                        ]);
                        
                        $post->update([
                            'status' => 'failed',
                            'platform_specific_data' => $publishResponse->json()
                        ]);
                        
                        return false;
                    }
                } else {
                    Log::error('Instagram media creation ID not returned', [
                        'response' => $responseData,
                        'post_id' => $post->id
                    ]);
                    
                    $post->update([
                        'status' => 'failed',
                        'platform_specific_data' => $responseData
                    ]);
                    
                    return false;
                }
            } else {
                Log::error('Instagram create media failed', [
                    'response' => $response->json(),
                    'status_code' => $response->status(),
                    'post_id' => $post->id
                ]);
                
                $post->update([
                    'status' => 'failed',
                    'platform_specific_data' => $response->json()
                ]);
                
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Instagram API Error', [
                'error' => $e->getMessage(),
                'post_id' => $post->id
            ]);
            
            $post->update([
                'status' => 'failed',
                'platform_specific_data' => ['error' => $e->getMessage()]
            ]);
            
            return false;
        }
    }

    private function publishToLinkedIn(Post $post, SocialAccount $account)
    {
        try {
            $payload = [
                'author' => "urn:li:person:{$account->account_id}",
                'lifecycleState' => 'PUBLISHED',
                'specificContent' => [
                    'com.linkedin.ugc.ShareContent' => [
                        'shareCommentary' => [
                            'text' => $post->content,
                        ],
                        'shareMediaCategory' => 'NONE',
                    ],
                ],
                'visibility' => [
                    'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
                ],
            ];
            
            // Add media if available
            if ($post->media_files && !empty($post->media_files)) {
                // For LinkedIn, we need to handle media differently - creating an image share
                $payload['specificContent']['com.linkedin.ugc.ShareContent']['shareMediaCategory'] = 'IMAGE';
                $payload['specificContent']['com.linkedin.ugc.ShareContent']['media'] = [
                    [
                        'status' => 'READY',
                        'description' => 'Description for the image',
                        'location' => $post->media_files[0],
                        'title' => [
                            'text' => 'Image title',
                        ],
                    ],
                ];
            }
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $account->access_token,
                'X-Restli-Protocol-Version' => '2.0.0',
                'Content-Type' => 'application/json',
            ])->post('https://api.linkedin.com/v' . SettingsHelper::get('linkedin_api_version', '2') . '/ugcPosts', $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                $post->update([
                    'platform_specific_data' => $responseData,
                    'status' => 'published',
                    'published_at' => now(),
                ]);
                
                return $responseData;
            } else {
                Log::error('LinkedIn post failed', [
                    'response' => $response->json(),
                    'status_code' => $response->status(),
                    'post_id' => $post->id
                ]);
                
                $post->update([
                    'status' => 'failed',
                    'platform_specific_data' => $response->json()
                ]);
                
                return false;
            }
        } catch (\Exception $e) {
            Log::error('LinkedIn API Error', [
                'error' => $e->getMessage(),
                'post_id' => $post->id
            ]);
            
            $post->update([
                'status' => 'failed',
                'platform_specific_data' => ['error' => $e->getMessage()]
            ]);
            
            return false;
        }
    }

    private function publishToTwitter(Post $post, SocialAccount $account)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $account->access_token,
            ])->post("https://api.twitter.com/v" . SettingsHelper::get('twitter_api_version', '2') . "/tweets", [
                'text' => $post->content,
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                $post->update([
                    'platform_specific_data' => $responseData,
                    'status' => 'published',
                    'published_at' => now(),
                ]);
                
                return $responseData;
            } else {
                Log::error('Twitter post failed', [
                    'response' => $response->json(),
                    'post_id' => $post->id
                ]);
                
                $post->update([
                    'status' => 'failed',
                    'platform_specific_data' => $response->json()
                ]);
                
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Twitter API Error', [
                'error' => $e->getMessage(),
                'post_id' => $post->id
            ]);
            
            $post->update([
                'status' => 'failed',
                'platform_specific_data' => ['error' => $e->getMessage()]
            ]);
            
            return false;
        }
    }

    private function publishToWhatsApp(Post $post, SocialAccount $account)
    {
        try {
            // WhatsApp Business API implementation - proper payload structure
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $account->account_id, // This should be the actual recipient phone number
                'type' => 'text',
                'text' => [
                    'body' => $post->content,
                ],
            ];
            
            // Add media if available
            if ($post->media_files && !empty($post->media_files)) {
                // For WhatsApp, use media message type instead of text
                unset($payload['text']);
                $payload['type'] = 'image';
                $payload['image'] = [
                    'link' => $post->media_files[0],
                    'caption' => $post->content,
                ];
            }
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $account->access_token,
                'Content-Type' => 'application/json',
            ])->post("https://graph.facebook.com/v" . SettingsHelper::get('whatsapp_api_version', '18.0') . "/{$account->phone_number_id}/messages", $payload); // Use phone_number_id instead of account_id

            if ($response->successful()) {
                $responseData = $response->json();
                $post->update([
                    'platform_specific_data' => $responseData,
                    'status' => 'published',
                    'published_at' => now(),
                ]);
                
                return $responseData;
            } else {
                Log::error('WhatsApp message failed', [
                    'response' => $response->json(),
                    'status_code' => $response->status(),
                    'post_id' => $post->id
                ]);
                
                $post->update([
                    'status' => 'failed',
                    'platform_specific_data' => $response->json()
                ]);
                
                return false;
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp API Error', [
                'error' => $e->getMessage(),
                'post_id' => $post->id
            ]);
            
            $post->update([
                'status' => 'failed',
                'platform_specific_data' => ['error' => $e->getMessage()]
            ]);
            
            return false;
        }
    }

    private function publishToX(Post $post, SocialAccount $account)
    {
        try {
            // Use the XService for publishing
            $xService = new \App\Services\SocialMedia\XService();
            return $xService->publishPost($post, $account);
        } catch (\Exception $e) {
            Log::error('X post publishing failed', [
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

    private function publishToTikTok(Post $post, SocialAccount $account)
    {
        try {
            // TikTok Content Posting API implementation
            // First, initialize the video upload
            $initResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $account->access_token,
                'Content-Type' => 'application/json',
            ])->post("https://open.tiktokapis.com/v2/post/publish/inbox/video/init/", [
                'source' => 'FILE_UPLOAD',
                'upload_parameters' => [
                    'video_size' => 0, // This would be calculated from the actual file
                    'chunk_size' => 5242880, // 5MB chunk size
                    'total_chunk_count' => 1,
                ]
            ]);
            
            if ($initResponse->successful()) {
                $initData = $initResponse->json();
                $publishId = $initData['data']['publish_id'] ?? null;
                $uploadUrl = $initData['data']['upload_url'] ?? null;
                
                if ($uploadUrl && $publishId) {
                    // Upload the video file to the provided URL
                    // This is a simplified implementation - in practice, we'd need to handle the file upload
                    
                    // After uploading, create the post with content
                    $publishResponse = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $account->access_token,
                        'Content-Type' => 'application/json',
                    ])->post("https://open.tiktokapis.com/v2/post/publish/video/direct/", [
                        'publish_id' => $publishId,
                        'text' => $post->content,
                        'privacy_level' => 'PUBLIC_TO_EVERYONE',
                        'disable_comment' => false,
                        'disable_duet' => false,
                        'disable_stitch' => false,
                    ]);
                    
                    if ($publishResponse->successful()) {
                        $responseData = $publishResponse->json();
                        $post->update([
                            'platform_specific_data' => $responseData,
                            'status' => 'published',
                            'published_at' => now(),
                        ]);
                        
                        return $responseData;
                    }
                }
            }
            
            Log::error('TikTok post failed', [
                'init_response' => $initResponse->json(),
                'init_status' => $initResponse->status(),
                'post_id' => $post->id
            ]);
            
            $post->update([
                'status' => 'failed',
                'platform_specific_data' => $initResponse->json()
            ]);
            
            return false;
        } catch (\Exception $e) {
            Log::error('TikTok API Error', [
                'error' => $e->getMessage(),
                'post_id' => $post->id
            ]);
            
            $post->update([
                'status' => 'failed',
                'platform_specific_data' => ['error' => $e->getMessage()]
            ]);
            
            return false;
        }
    }

    private function publishToYouTube(Post $post, SocialAccount $account)
    {
        try {
            // YouTube Data API v3 implementation for uploading videos
            // This is a simplified implementation - actual file upload requires multipart upload
            $payload = [
                'snippet' => [
                    'title' => substr($post->content, 0, 100) ?: 'Untitled Video',
                    'description' => $post->content,
                    'tags' => ['automation', 'social_media'],
                    'categoryId' => '22', // People & Blogs category
                ],
                'status' => [
                    'privacyStatus' => 'private', // Start as private, can be updated later
                ]
            ];
            
            // For actual video upload, we would need to use multipart upload
            // This is a placeholder for the actual implementation
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $account->access_token,
                'Content-Type' => 'application/json',
            ])->post("https://www.googleapis.com/upload/youtube/v3/videos?uploadType=resumable&part=snippet,status");
            
            // Handle resumable upload process
            if ($response->successful()) {
                $responseData = $response->json();
                $post->update([
                    'platform_specific_data' => $responseData,
                    'status' => 'published',
                    'published_at' => now(),
                ]);
                
                return $responseData;
            } else {
                Log::error('YouTube post failed', [
                    'response' => $response->json(),
                    'status_code' => $response->status(),
                    'post_id' => $post->id
                ]);
                
                $post->update([
                    'status' => 'failed',
                    'platform_specific_data' => $response->json()
                ]);
                
                return false;
            }
        } catch (\Exception $e) {
            Log::error('YouTube API Error', [
                'error' => $e->getMessage(),
                'post_id' => $post->id
            ]);
            
            $post->update([
                'status' => 'failed',
                'platform_specific_data' => ['error' => $e->getMessage()]
            ]);
            
            return false;
        }
    }

    private function refreshXToken(SocialAccount $account)
    {
        if (!$account->refresh_token) {
            return false;
        }

        // X OAuth 2.0 token refresh implementation
        $response = Http::asForm()->withBasicAuth(
            SettingsHelper::get('x_client_id'),
            SettingsHelper::get('x_client_secret')
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
            
            return true;
        }
        
        Log::error('X token refresh failed', [
            'status_code' => $response->status(),
            'response' => $response->json(),
            'account_id' => $account->id,
        ]);
        
        return false;
    }
    
    public function refreshToken(SocialAccount $account)
    {
        try {
            switch ($account->platform) {
                case 'facebook':
                case 'instagram':
                    return $this->refreshFacebookToken($account);
                case 'linkedin':
                    return $this->refreshLinkedInToken($account);
                case 'x':
                    return $this->refreshXToken($account);
                default:
                    return false;
            }
        } catch (\Exception $e) {
            Log::error('Token refresh failed', [
                'platform' => $account->platform,
                'account_id' => $account->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    private function refreshLinkedInToken(SocialAccount $account)
    {
        if (!$account->refresh_token) {
            return false;
        }

        $response = Http::asForm()->post('https://www.linkedin.com/oauth/v2/accessToken', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $account->refresh_token,
            'client_id' => SettingsHelper::get('linkedin_client_id'),
            'client_secret' => SettingsHelper::get('linkedin_client_secret'),
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $account->update([
                'access_token' => $data['access_token'],
                'expires_in' => $data['expires_in'],
                'token_expires_at' => now()->addSeconds($data['expires_in']),
                'refresh_token' => $data['refresh_token'] ?? $account->refresh_token,
            ]);
            
            return true;
        } else {
            Log::error('LinkedIn token refresh failed', [
                'status_code' => $response->status(),
                'response' => $response->json(),
                'account_id' => $account->id,
            ]);
            
            return false;
        }
    }

    private function publishToPinterest(Post $post, SocialAccount $account)
    {
        try {
            $boardId = $account->account_id;
            
            $payload = [
                'note' => $post->content,
                'board' => $boardId,
            ];
            
            if ($post->media_files && !empty($post->media_files)) {
                $payload['image_url'] = $post->media_files[0];
            }
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $account->access_token,
            ])->post("https://api.pinterest.com/v" . SettingsHelper::get('pinterest_api_version', '5') . "/pins", $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                $post->update([
                    'platform_specific_data' => $responseData,
                    'status' => 'published',
                    'published_at' => now(),
                ]);
                
                return $responseData;
            } else {
                Log::error('Pinterest post failed', [
                    'response' => $response->json(),
                    'status_code' => $response->status(),
                    'post_id' => $post->id
                ]);
                
                $post->update([
                    'status' => 'failed',
                    'platform_specific_data' => $response->json()
                ]);
                
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Pinterest API Error', [
                'error' => $e->getMessage(),
                'post_id' => $post->id
            ]);
            
            $post->update([
                'status' => 'failed',
                'platform_specific_data' => ['error' => $e->getMessage()]
            ]);
            
            return false;
        }
    }

    private function publishToSnapchat(Post $post, SocialAccount $account)
    {
        try {
            $payload = [
                'text' => $post->content,
            ];
            
            if ($post->media_files && !empty($post->media_files)) {
                $payload['media_url'] = $post->media_files[0];
            }
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $account->access_token,
                'Content-Type' => 'application/json',
            ])->post("https://api.snapkit.com/v1/posts", $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                $post->update([
                    'platform_specific_data' => $responseData,
                    'status' => 'published',
                    'published_at' => now(),
                ]);
                
                return $responseData;
            } else {
                Log::error('Snapchat post failed', [
                    'response' => $response->json(),
                    'status_code' => $response->status(),
                    'post_id' => $post->id
                ]);
                
                $post->update([
                    'status' => 'failed',
                    'platform_specific_data' => $response->json()
                ]);
                
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Snapchat API Error', [
                'error' => $e->getMessage(),
                'post_id' => $post->id
            ]);
            
            $post->update([
                'status' => 'failed',
                'platform_specific_data' => ['error' => $e->getMessage()]
            ]);
            
            return false;
        }
    }

    private function publishToReddit(Post $post, SocialAccount $account)
    {
        try {
            $payload = [
                'title' => substr($post->content, 0, 300),
                'body' => $post->content,
                'subreddit' => $account->account_id,
            ];
            
            if ($post->media_files && !empty($post->media_files)) {
                $payload['url'] = $post->media_files[0];
            }
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $account->access_token,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->post("https://oauth.reddit.com/api/submit", $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                $post->update([
                    'platform_specific_data' => $responseData,
                    'status' => 'published',
                    'published_at' => now(),
                ]);
                
                return $responseData;
            } else {
                Log::error('Reddit post failed', [
                    'response' => $response->json(),
                    'status_code' => $response->status(),
                    'post_id' => $post->id
                ]);
                
                $post->update([
                    'status' => 'failed',
                    'platform_specific_data' => $response->json()
                ]);
                
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Reddit API Error', [
                'error' => $e->getMessage(),
                'post_id' => $post->id
            ]);
            
            $post->update([
                'status' => 'failed',
                'platform_specific_data' => ['error' => $e->getMessage()]
            ]);
            
            return false;
        }
    }

    private function publishToTelegram(Post $post, SocialAccount $account)
    {
        try {
            $chatId = $account->account_id;
            
            $payload = [
                'chat_id' => $chatId,
                'text' => $post->content,
            ];

            if ($post->media_files && !empty($post->media_files)) {
                $response = Http::asMultipart()->post("https://api.telegram.org/bot{$account->access_token}/sendPhoto", [
                    'chat_id' => $chatId,
                    'photo' => $post->media_files[0],
                    'caption' => $post->content,
                ]);
            } else {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $account->access_token,
                ])->post("https://api.telegram.org/bot{$account->access_token}/sendMessage", $payload);
            }

            if ($response->successful()) {
                $responseData = $response->json();
                $post->update([
                    'platform_specific_data' => $responseData,
                    'status' => 'published',
                    'published_at' => now(),
                ]);
                
                return $responseData;
            } else {
                Log::error('Telegram post failed', [
                    'response' => $response->json(),
                    'status_code' => $response->status(),
                    'post_id' => $post->id
                ]);
                
                $post->update([
                    'status' => 'failed',
                    'platform_specific_data' => $response->json()
                ]);
                
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Telegram API Error', [
                'error' => $e->getMessage(),
                'post_id' => $post->id
            ]);
            
            $post->update([
                'status' => 'failed',
                'platform_specific_data' => ['error' => $e->getMessage()]
            ]);
            
            return false;
        }
    }

    private function publishToDiscord(Post $post, SocialAccount $account)
    {
        try {
            $channelId = $account->account_id;
            
            $payload = [
                'content' => $post->content,
            ];
            
            if ($post->media_files && !empty($post->media_files)) {
                $payload['content'] .= '\n' . $post->media_files[0];
            }
            
            $response = Http::withHeaders([
                'Authorization' => 'Bot ' . $account->access_token,
                'Content-Type' => 'application/json',
            ])->post("https://discord.com/api/channels/{$channelId}/messages", $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                $post->update([
                    'platform_specific_data' => $responseData,
                    'status' => 'published',
                    'published_at' => now(),
                ]);
                
                return $responseData;
            } else {
                Log::error('Discord post failed', [
                    'response' => $response->json(),
                    'status_code' => $response->status(),
                    'post_id' => $post->id
                ]);
                
                $post->update([
                    'status' => 'failed',
                    'platform_specific_data' => $response->json()
                ]);
                
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Discord API Error', [
                'error' => $e->getMessage(),
                'post_id' => $post->id
            ]);
            
            $post->update([
                'status' => 'failed',
                'platform_specific_data' => ['error' => $e->getMessage()]
            ]);
            
            return false;
        }
    }

    private function publishToTumblr(Post $post, SocialAccount $account)
    {
        try {
            $blogName = $account->account_id;
            
            $payload = [
                'type' => 'text',
                'body' => $post->content,
                'state' => 'published',
            ];
            
            if ($post->media_files && !empty($post->media_files)) {
                $payload['type'] = 'photo';
                $payload['source'] = $post->media_files[0];
            }
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $account->access_token,
            ])->post("https://api.tumblr.com/v2/blog/{$blogName}/post", $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                $post->update([
                    'platform_specific_data' => $responseData,
                    'status' => 'published',
                    'published_at' => now(),
                ]);
                
                return $responseData;
            } else {
                Log::error('Tumblr post failed', [
                    'response' => $response->json(),
                    'status_code' => $response->status(),
                    'post_id' => $post->id
                ]);
                
                $post->update([
                    'status' => 'failed',
                    'platform_specific_data' => $response->json()
                ]);
                
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Tumblr API Error', [
                'error' => $e->getMessage(),
                'post_id' => $post->id
            ]);
            
            $post->update([
                'status' => 'failed',
                'platform_specific_data' => ['error' => $e->getMessage()]
            ]);
            
            return false;
        }
    }

    private function publishToFlickr(Post $post, SocialAccount $account)
    {
        try {
            $payload = [
                'title' => substr($post->content, 0, 200),
                'description' => $post->content,
            ];
            
            if ($post->media_files && !empty($post->media_files)) {
                $payload['photo'] = $post->media_files[0];
            }
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $account->access_token,
            ])->post("https://api.flickr.com/services/rest/?method=flickr.photos.upload.public",
                array_merge($payload, [
                    'api_key' => SettingsHelper::get('flickr_api_key'),
                    'format' => 'json',
                    'nojsoncallback' => 1,
                ])
            );

            if ($response->successful()) {
                $responseData = $response->json();
                $post->update([
                    'platform_specific_data' => $responseData,
                    'status' => 'published',
                    'published_at' => now(),
                ]);
                
                return $responseData;
            } else {
                Log::error('Flickr post failed', [
                    'response' => $response->json(),
                    'status_code' => $response->status(),
                    'post_id' => $post->id
                ]);
                
                $post->update([
                    'status' => 'failed',
                    'platform_specific_data' => $response->json()
                ]);
                
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Flickr API Error', [
                'error' => $e->getMessage(),
                'post_id' => $post->id
            ]);
            
            $post->update([
                'status' => 'failed',
                'platform_specific_data' => ['error' => $e->getMessage()]
            ]);
            
            return false;
        }
    }

    private function publishToVimeo(Post $post, SocialAccount $account)
    {
        try {
            $payload = [
                'name' => substr($post->content, 0, 255) ?: 'Video Post',
                'description' => $post->content,
            ];
            
            if ($post->media_files && !empty($post->media_files)) {
                $payload['file'] = $post->media_files[0];
            }
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $account->access_token,
                'Content-Type' => 'application/json',
            ])->post("https://api.vimeo.com/me/videos", $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                $post->update([
                    'platform_specific_data' => $responseData,
                    'status' => 'published',
                    'published_at' => now(),
                ]);
                
                return $responseData;
            } else {
                Log::error('Vimeo post failed', [
                    'response' => $response->json(),
                    'status_code' => $response->status(),
                    'post_id' => $post->id
                ]);
                
                $post->update([
                    'status' => 'failed',
                    'platform_specific_data' => $response->json()
                ]);
                
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Vimeo API Error', [
                'error' => $e->getMessage(),
                'post_id' => $post->id
            ]);
            
            $post->update([
                'status' => 'failed',
                'platform_specific_data' => ['error' => $e->getMessage()]
            ]);
            
            return false;
        }
    }

    private function publishToTwitch(Post $post, SocialAccount $account)
    {
        try {
            $payload = [
                'broadcaster_id' => $account->account_id,
                'message' => $post->content,
            ];
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $account->access_token,
                'Client-Id' => SettingsHelper::get('twitch_client_id'),
            ])->post("https://api.twitch.tv/helix/chat/messages", $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                $post->update([
                    'platform_specific_data' => $responseData,
                    'status' => 'published',
                    'published_at' => now(),
                ]);
                
                return $responseData;
            } else {
                Log::error('Twitch post failed', [
                    'response' => $response->json(),
                    'status_code' => $response->status(),
                    'post_id' => $post->id
                ]);
                
                $post->update([
                    'status' => 'failed',
                    'platform_specific_data' => $response->json()
                ]);
                
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Twitch API Error', [
                'error' => $e->getMessage(),
                'post_id' => $post->id
            ]);
            
            $post->update([
                'status' => 'failed',
                'platform_specific_data' => ['error' => $e->getMessage()]
            ]);
            
            return false;
        }
    }
}