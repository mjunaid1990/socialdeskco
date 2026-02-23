<?php

namespace App\Services\SocialMedia;

use App\Models\Post;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LinkedInService extends BaseSocialMediaService
{
    // FIX: LinkedIn ugcPosts API is deprecated. Use the Posts API (v2) instead.
    // https://learn.microsoft.com/en-us/linkedin/marketing/community-management/shares/posts-api

    public function publishPost(Post $post, SocialAccount $account)
    {
        $existingData = $post->platform_specific_data;
        if (is_array($existingData) && !empty($existingData['post_id'])) {
            Log::info('LinkedIn post already published, skipping', [
                'post_id'          => $post->id,
                'linkedin_post_id' => $existingData['post_id'],
            ]);
            return $existingData;
        }
        try {
            $authorUrn = "urn:li:person:{$account->account_id}";

            $payload = [
                'author'          => $authorUrn,
                'commentary'      => $post->content . ' ' . ($post->linkedin_settings['hashtags'] ?? ''),
                'visibility'      => 'PUBLIC',
                'distribution'    => [
                    'feedDistribution'               => 'MAIN_FEED',
                    'targetEntities'                 => [],
                    'thirdPartyDistributionChannels' => [],
                ],
                'lifecycleState'  => 'PUBLISHED',
                'isReshareDisabledByAuthor' => false,
            ];

            // Handle media
            if ($post->media_files && !empty($post->media_files)) {
                $mediaUrl       = $post->media_files[0];
                $fileExtension  = strtolower(pathinfo($mediaUrl, PATHINFO_EXTENSION));
                $videoExtensions = ['mp4', 'mov', 'avi', 'mkv', 'wmv', 'flv', 'webm'];
                $isVideo        = in_array($fileExtension, $videoExtensions);

                $mediaUrn = $this->uploadMediaToLinkedIn($mediaUrl, $account, $isVideo);

                if ($mediaUrn && strpos($mediaUrn, 'digitalmediaAsset') === false) {
                    $payload['content'] = [
                        'media' => [
                            'id'    => $mediaUrn,
                            'title' => $post->title ?? ($isVideo ? 'Shared Video' : 'Shared Image'),
                        ],
                    ];
                } else {
                    Log::warning('LinkedInService: Media not ready or wrong URN type, posting as text-only', [
                        'post_id'   => $post->id,
                        'media_url' => $mediaUrl,
                        'media_urn' => $mediaUrn,
                    ]);
                }
            }

            $headers = [
                'Authorization'              => 'Bearer ' . $this->getAccessToken($account),
                'X-Restli-Protocol-Version'  => '2.0.0',
                'Content-Type'               => 'application/json',
                'LinkedIn-Version'           => '202602',
            ];

            // FIX: Use /rest/posts (new Posts API) instead of deprecated ugcPosts
            $response = $this->makeApiRequest('post',
                'https://api.linkedin.com/rest/posts',
                $payload,
                $headers
            );

            
            if ($response->successful()) {
                $responseData = $response->json();
                // FIX: Posts API returns the post URN in the x-restli-id header
                $postId = $response->header('x-restli-id') ?? $responseData['id'] ?? null;

                $post->update([
                    'platform_specific_data' => array_merge($responseData ?? [], ['post_id' => $postId]),
                    'status'                 => 'published',
                    'published_at'           => now(),
                ]);

                Log::info('LinkedIn post published successfully', [
                    'post_id'          => $post->id,
                    'linkedin_post_id' => $postId,
                ]);

                // Return the correct structure for SocialMediaService to detect success
                return [
                    'post_id' => $postId
                ];
            } else {
                Log::error('LinkedIn post failed', [
                    'response'    => $response->json(),
                    'status_code' => $response->status(),
                    'post_id'     => $post->id,
                ]);

                $post->update([
                    'status' => 'failed',
                    'platform_specific_data' => [
                        'error'       => 'LinkedIn API Error',
                        'response'    => $response->json(),
                        'status_code' => $response->status(),
                    ],
                ]);

                return false;
            }
        } catch (\Exception $e) {
            Log::error('LinkedIn API Error', [
                'error'   => $e->getMessage(),
                'post_id' => $post->id,
            ]);

            $post->update([
                'status' => 'failed',
                'platform_specific_data' => ['error' => $e->getMessage()],
            ]);

            return false;
        }
    }

    private function uploadMediaToLinkedIn(string $mediaUrl, SocialAccount $account, bool $isVideo = false): ?string
    {
        try {
            $recipe = $isVideo
                ? 'urn:li:digitalmediaRecipe:feedshare-video'
                : 'urn:li:digitalmediaRecipe:feedshare-image';

            $registerPayload = [
                'registerUploadRequest' => [
                    'recipes'              => [$recipe],
                    'owner'                => "urn:li:person:{$account->account_id}",
                    'serviceRelationships' => [
                        [
                            'relationshipType' => 'OWNER',
                            'identifier'       => 'urn:li:userGeneratedContent',
                        ],
                    ],
                ],
            ];

            $registerResponse = $this->makeApiRequest('post',
                'https://api.linkedin.com/v2/assets?action=registerUpload',
                $registerPayload,
                [
                    'Authorization'             => 'Bearer ' . $this->getAccessToken($account),
                    'X-Restli-Protocol-Version' => '2.0.0',
                    'Content-Type'              => 'application/json',
                ]
            );

            if (!$registerResponse->successful()) {
                Log::error('LinkedInService: Failed to register media upload', [
                    'response'    => $registerResponse->json(),
                    'status_code' => $registerResponse->status(),
                ]);
                return null;
            }

            $registerData = $registerResponse->json();
            $uploadUrl    = $registerData['value']['uploadMechanism']['com.linkedin.digitalmedia.uploading.MediaUploadHttpRequest']['uploadUrl'] ?? null;
            $imageAsset   = $registerData['value']['asset'] ?? null;

            if (!$uploadUrl || !$imageAsset) {
                Log::error('LinkedInService: Invalid register upload response', ['response' => $registerData]);
                return null;
            }

            $mediaContent = @file_get_contents($mediaUrl);
            if ($mediaContent === false) {
                Log::error('LinkedInService: Failed to download media', ['media_url' => $mediaUrl]);
                return null;
            }

            $fileExtension = strtolower(pathinfo($mediaUrl, PATHINFO_EXTENSION));
            $mimeTypes = [
                'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',  'gif'  => 'image/gif',
                'mp4' => 'video/mp4',  'mov'  => 'video/quicktime',
            ];
            $mimeType = $mimeTypes[$fileExtension] ?? ($isVideo ? 'video/mp4' : 'image/jpeg');

            $tempFile = tempnam(sys_get_temp_dir(), 'linkedin_upload_');
            file_put_contents($tempFile, $mediaContent);

            $uploadResponse = Http::attach(
                'file',
                file_get_contents($tempFile),
                basename($mediaUrl),
                ['Content-Type' => $mimeType]
            )->withHeaders([
                'Authorization' => 'Bearer ' . $this->getAccessToken($account),
            ])->post($uploadUrl);

            @unlink($tempFile);

            if (!$uploadResponse->successful()) {
                Log::error('LinkedInService: Failed to upload media to LinkedIn', [
                    'status_code' => $uploadResponse->status(),
                ]);
                return null;
            }

            return $imageAsset;
        } catch (\Exception $e) {
            Log::error('LinkedInService: Error uploading media', [
                'error'     => $e->getMessage(),
                'media_url' => $mediaUrl,
            ]);
            return null;
        }
    }

    public function deletePost(Post $post, SocialAccount $account)
    {
        try {
            $platformData   = $post->platform_specific_data;
            $linkedinPostId = null;

            Log::info('LinkedInService: deletePost called', [
                'post_id' => $post->id,
                'platform_specific_data' => $platformData
            ]);

            if (is_array($platformData)) {
                // Check multiple possible locations for LinkedIn post ID
                $linkedinPostId = $platformData['post_id'] ?? 
                                $platformData['id'] ?? 
                                ($platformData['results'][$account->id]['post_id'] ?? 
                                ($platformData['results'][$account->id]['id'] ?? null));
            }

            if (!$linkedinPostId) {
                Log::warning('LinkedInService: No LinkedIn post ID found', [
                    'post_id' => $post->id,
                    'account_id' => $account->id,
                    'platform_data' => $platformData
                ]);
                return false;
            }

            Log::info('LinkedInService: Deleting LinkedIn post', ['linkedin_post_id' => $linkedinPostId]);

            $encodedPostId = urlencode($linkedinPostId);

            // FIX: Use /rest/posts endpoint (Posts API) instead of deprecated ugcPosts
            $response = Http::withHeaders([
                'Authorization'             => 'Bearer ' . $this->getAccessToken($account),
                'X-Restli-Protocol-Version' => '2.0.0',
                'LinkedIn-Version'          => '202602',
            ])->delete("https://api.linkedin.com/rest/posts/{$encodedPostId}");

            Log::info('LinkedInService: Delete API response', [
                'status_code' => $response->status(),
                'successful' => $response->successful(),
                'response_body' => $response->body()
            ]);

            if ($response->successful() || $response->status() === 204) {
                Log::info('LinkedInService: Post deleted successfully', ['linkedin_post_id' => $linkedinPostId]);
                return true;
            } else {
                Log::error('LinkedInService: Failed to delete post', [
                    'linkedin_post_id' => $linkedinPostId,
                    'response'         => $response->json(),
                    'status_code'      => $response->status(),
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('LinkedInService: Error deleting post', [
                'error'   => $e->getMessage(),
                'post_id' => $post->id,
                'trace'   => $e->getTraceAsString()
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
            'grant_type'    => 'refresh_token',
            'refresh_token' => $this->getRefreshToken($account),
            'client_id'     => $this->getClientId('linkedin'),
            'client_secret' => $this->getClientSecret('linkedin'),
        ]);

        if ($response->successful()) {
            $this->updateAccountTokens($account, $response->json());
            return true;
        }

        $this->logApiError('LinkedIn token refresh failed', [
            'status_code' => $response->status(),
            'response'    => $response->json(),
            'account_id'  => $account->id,
        ]);
        return false;
    }

    public function getPlatformName(): string
    {
        return 'linkedin';
    }
}