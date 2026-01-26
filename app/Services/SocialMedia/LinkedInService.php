<?php

namespace App\Services\SocialMedia;

use App\Models\Post;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class LinkedInService extends BaseSocialMediaService
{
    public function publishPost(Post $post, SocialAccount $account)
    {
        try {
            // Base payload for text-only post using UGC API
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
            
            // Handle media if available
            if ($post->media_files && !empty($post->media_files)) {
                $mediaUrl = $post->media_files[0];
                $fileExtension = strtolower(pathinfo($mediaUrl, PATHINFO_EXTENSION));
                $videoExtensions = ['mp4', 'mov', 'avi', 'mkv', 'wmv', 'flv', 'webm'];
                $isVideo = in_array($fileExtension, $videoExtensions);
                
                $mediaUrn = $this->uploadImageToLinkedInShareAPI($mediaUrl, $account);
                if ($mediaUrn) {
                    $shareMediaCategory = $isVideo ? 'VIDEO' : 'IMAGE';
                    $payload['specificContent']['com.linkedin.ugc.ShareContent']['shareMediaCategory'] = $shareMediaCategory;
                    $payload['specificContent']['com.linkedin.ugc.ShareContent']['media'] = [
                        [
                            'status' => 'READY',
                            'description' => [
                                'text' => $post->title ?? ($isVideo ? 'Shared Video' : 'Shared Image'),
                            ],
                            'media' => $mediaUrn,
                            'title' => [
                                'text' => $post->title ?? ($isVideo ? 'Shared Video' : 'Shared Image'),
                            ],
                        ],
                    ];
                    
                    Log::info('LinkedInService: Media uploaded successfully via Share API', [
                        'media_urn' => $mediaUrn,
                        'post_id' => $post->id,
                        'is_video' => $isVideo,
                        'share_media_category' => $shareMediaCategory
                    ]);
                } else {
                    Log::warning('LinkedInService: Failed to upload media via Share API, posting as text-only', [
                        'post_id' => $post->id,
                        'media_url' => $mediaUrl,
                        'is_video' => $isVideo
                    ]);
                }
            }
            
            $headers = [
                'Authorization' => 'Bearer ' . $this->getAccessToken($account),
                'X-Restli-Protocol-Version' => '2.0.0',
                'Content-Type' => 'application/json',
            ];
            
            $response = $this->makeApiRequest('post',
                'https://api.linkedin.com/v2/ugcPosts',
                $payload,
                $headers
            );
            
            if ($response->successful()) {
                $responseData = $response->json();
                $postId = $response->header('x-restli-id') ?? $responseData['id'] ?? null;
                
                $post->update([
                    'platform_specific_data' => array_merge($responseData, ['post_id' => $postId]),
                    'status' => 'published',
                    'published_at' => now(),
                ]);
                
                Log::info('LinkedIn post published successfully via Share API', [
                    'post_id' => $post->id,
                    'linkedin_post_id' => $postId,
                    'platform' => 'linkedin'
                ]);
                
                return $responseData;
            } else {
                Log::error('LinkedIn post failed via Share API', [
                    'response' => $response->json(),
                    'status_code' => $response->status(),
                    'post_id' => $post->id
                ]);
                
                $post->update([
                    'status' => 'failed',
                    'platform_specific_data' => [
                        'error' => 'LinkedIn Share API Error',
                        'response' => $response->json(),
                        'status_code' => $response->status()
                    ]
                ]);
                
                return false;
            }
        } catch (\Exception $e) {
            Log::error('LinkedIn Share API Error', [
                'error' => $e->getMessage(),
                'post_id' => $post->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            $post->update([
                'status' => 'failed',
                'platform_specific_data' => ['error' => $e->getMessage()]
            ]);
            
            return false;
        }
    }
    
    private function uploadImageToLinkedInShareAPI($imageUrl, SocialAccount $account)
    {
        try {
            Log::info('LinkedInService: Starting media upload via Share API', [
                'image_url' => $imageUrl,
                'account_id' => $account->account_id
            ]);
            
            // Detect file type from URL
            $isVideo = false;
            $fileExtension = strtolower(pathinfo($imageUrl, PATHINFO_EXTENSION));
            $videoExtensions = ['mp4', 'mov', 'avi', 'mkv', 'wmv', 'flv', 'webm'];
            
            if (in_array($fileExtension, $videoExtensions)) {
                $isVideo = true;
            }
            
            // Step 1: Register upload using Share API assets endpoint
            $recipe = $isVideo ? 'urn:li:digitalmediaRecipe:feedshare-video' : 'urn:li:digitalmediaRecipe:feedshare-image';
            
            $registerPayload = [
                'registerUploadRequest' => [
                    'recipes' => [$recipe],
                    'owner' => "urn:li:person:{$account->account_id}",
                    'serviceRelationships' => [
                        [
                            'relationshipType' => 'OWNER',
                            'identifier' => 'urn:li:userGeneratedContent'
                        ]
                    ]
                ]
            ];
            
            Log::info('LinkedInService: Registering media upload', [
                'is_video' => $isVideo,
                'recipe' => $recipe,
                'file_extension' => $fileExtension
            ]);
            
            $registerResponse = $this->makeApiRequest('post',
                'https://api.linkedin.com/v2/assets?action=registerUpload',
                $registerPayload,
                [
                    'Authorization' => 'Bearer ' . $this->getAccessToken($account),
                    'X-Restli-Protocol-Version' => '2.0.0',
                    'Content-Type' => 'application/json',
                ]
            );
            
            Log::info('LinkedInService: Register upload response', [
                'status_code' => $registerResponse->status(),
                'successful' => $registerResponse->successful(),
                'response_body' => $registerResponse->body(),
                'response_json' => $registerResponse->json()
            ]);
            
            if (!$registerResponse->successful()) {
                Log::error('LinkedInService: Failed to register image upload via Share API', [
                    'response' => $registerResponse->json(),
                    'status_code' => $registerResponse->status(),
                    'body' => $registerResponse->body()
                ]);
                return false;
            }
            
            $registerData = $registerResponse->json();
            $uploadUrl = $registerData['value']['uploadMechanism']['com.linkedin.digitalmedia.uploading.MediaUploadHttpRequest']['uploadUrl'] ?? null;
            $imageAsset = $registerData['value']['asset'] ?? null;
            
            if (!$uploadUrl || !$imageAsset) {
                Log::error('LinkedInService: Invalid register upload response via Share API', [
                    'response' => $registerData
                ]);
                return false;
            }
            
            // Step 2: Upload the actual image as multipart/form-data
            $imageContent = file_get_contents($imageUrl);
            if ($imageContent === false) {
                Log::error('LinkedInService: Failed to download image for Share API', [
                    'image_url' => $imageUrl
                ]);
                return false;
            }
            
            // Create a temporary file to upload
            $tempFile = tempnam(sys_get_temp_dir(), 'linkedin_upload_');
            file_put_contents($tempFile, $imageContent);
            
            Log::info('LinkedInService: Starting multipart image upload', [
                'upload_url' => $uploadUrl,
                'temp_file' => $tempFile,
                'image_size' => strlen($imageContent)
            ]);
            
            // Upload as multipart/form-data with correct MIME type
            $mimeType = $isVideo ? 'video/mp4' : 'image/png';
            if (!$isVideo) {
                // Better image MIME type detection
                $imageExtensions = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif'];
                if (isset($imageExtensions[$fileExtension])) {
                    $mimeType = $imageExtensions[$fileExtension];
                }
            }
            
            $uploadResponse = Http::attach(
                'file', 
                file_get_contents($tempFile), 
                basename($imageUrl),
                ['Content-Type' => $mimeType]
            )->withHeaders([
                'Authorization' => 'Bearer ' . $this->getAccessToken($account),
            ])->post($uploadUrl);
            
            // Clean up temp file
            unlink($tempFile);
            
            Log::info('LinkedInService: Multipart upload response', [
                'status_code' => $uploadResponse->status(),
                'successful' => $uploadResponse->successful(),
                'response_body' => $uploadResponse->body(),
                'response_headers' => $uploadResponse->headers()
            ]);
            
            if (!$uploadResponse->successful()) {
                Log::error('LinkedInService: Failed to upload image to LinkedIn via Share API', [
                    'response' => $uploadResponse->body(),
                    'status_code' => $uploadResponse->status(),
                    'headers' => $uploadResponse->headers()
                ]);
                return false;
            }
            
            Log::info('LinkedInService: Image uploaded successfully via Share API', [
                'image_asset' => $imageAsset
            ]);
            
            return $imageAsset;
            
        } catch (\Exception $e) {
            Log::error('LinkedInService: Error uploading image via Share API', [
                'error' => $e->getMessage(),
                'image_url' => $imageUrl,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
    
    public function deletePost(Post $post, SocialAccount $account)
    {
        try {
            Log::info('LinkedInService: Starting deletePost for post ID: ' . $post->id);
            
            // Get the LinkedIn post ID from platform_specific_data
            $platformData = $post->platform_specific_data;
            $linkedinPostId = null;
            
            if (is_array($platformData)) {
                // Try to get post ID from various possible locations
                $linkedinPostId = $platformData['post_id'] ?? $platformData['id'] ?? null;
            }
            
            if (!$linkedinPostId) {
                Log::warning('LinkedInService: No LinkedIn post ID found for post ID: ' . $post->id);
                return false;
            }
            
            Log::info('LinkedInService: Attempting to delete LinkedIn post ID: ' . $linkedinPostId);
            
            // Delete the post using LinkedIn API
            // URL-encode the post ID to handle colons and other special characters
            $encodedPostId = urlencode($linkedinPostId);
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->getAccessToken($account),
                'X-Restli-Protocol-Version' => '2.0.0',
            ])->delete('https://api.linkedin.com/v' . $this->getApiVersion('linkedin', '2') . '/ugcPosts/' . $encodedPostId);
            
            if ($response->successful()) {
                Log::info('LinkedInService: Successfully deleted LinkedIn post ID: ' . $linkedinPostId);
                return true;
            } else {
                Log::error('LinkedInService: Failed to delete post', [
                    'linkedin_post_id' => $linkedinPostId,
                    'response' => $response->json(),
                    'status_code' => $response->status(),
                    'post_id' => $post->id
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('LinkedInService: Error deleting post', [
                'error' => $e->getMessage(),
                'post_id' => $post->id,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    public function refreshToken(SocialAccount $account): bool
    {
        if (!$this->getRefreshToken($account)) {
            return false;
        }

        $response = Http::asForm()->post('https://www.linkedin.com/oauth/v2/accessToken', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->getRefreshToken($account),
            'client_id' => $this->getClientId('linkedin'),
            'client_secret' => $this->getClientSecret('linkedin'),
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $this->updateAccountTokens($account, $data);
            return true;
        } else {
            $this->logApiError('LinkedIn token refresh failed', [
                'status_code' => $response->status(),
                'response' => $response->json(),
                'account_id' => $account->id,
            ]);
            return false;
        }
    }

    public function getPlatformName(): string
    {
        return 'linkedin';
    }
}
