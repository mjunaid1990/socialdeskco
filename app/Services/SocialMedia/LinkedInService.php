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

                if ($mediaUrn) {
                    $payload['content'] = [
                        'media' => [
                            'id'    => $mediaUrn,
                            'title' => $post->title ?? ($isVideo ? 'Shared Video' : 'Shared Image'),
                        ],
                    ];
                    Log::info('LinkedInService: Using media asset in post', [
                        'post_id' => $post->id,
                        'media_urn' => $mediaUrn,
                    ]);
                } else {
                    Log::warning('LinkedInService: No media asset available, posting as text-only', [
                        'post_id' => $post->id,
                        'media_url' => $mediaUrl,
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
            if ($isVideo) {
                return $this->uploadVideoToLinkedIn($mediaUrl, $account);
            } else {
                return $this->uploadImageToLinkedIn($mediaUrl, $account);
            }
        } catch (\Exception $e) {
            Log::error('LinkedInService: Error uploading media', [
                'error'     => $e->getMessage(),
                'media_url' => $mediaUrl,
                'trace'     => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    private function uploadImageToLinkedIn(string $mediaUrl, SocialAccount $account): ?string
    {
        // Use new Images API instead of Assets API
        $initializePayload = [
            'initializeUploadRequest' => [
                'owner' => "urn:li:person:{$account->account_id}",
            ],
        ];

        $initializeResponse = Http::withHeaders([
            'Authorization'             => 'Bearer ' . $this->getAccessToken($account),
            'X-Restli-Protocol-Version' => '2.0.0',
            'Content-Type'              => 'application/json',
            'LinkedIn-Version'           => '202602',
        ])->post('https://api.linkedin.com/rest/images?action=initializeUpload', $initializePayload);

        if (!$initializeResponse->successful()) {
            Log::error('LinkedInService: Failed to initialize image upload', [
                'response'    => $initializeResponse->json(),
                'status_code' => $initializeResponse->status(),
            ]);
            return null;
        }

        $initializeData = $initializeResponse->json();
        $uploadUrl = $initializeData['value']['uploadUrl'] ?? null;
        $imageUrn   = $initializeData['value']['image'] ?? null;

        if (!$uploadUrl || !$imageUrn) {
            Log::error('LinkedInService: Invalid initialize upload response', ['response' => $initializeData]);
            return null;
        }

        Log::info('LinkedInService: Initialized image upload', [
            'image_urn' => $imageUrn,
            'upload_url' => $uploadUrl,
        ]);

        // Download and upload image
        $mediaContent = @file_get_contents($mediaUrl);
        if ($mediaContent === false) {
            Log::error('LinkedInService: Failed to download media', ['media_url' => $mediaUrl]);
            return null;
        }

        $fileExtension = strtolower(pathinfo($mediaUrl, PATHINFO_EXTENSION));
        $mimeTypes = [
            'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',  'gif'  => 'image/gif',
        ];
        $mimeType = $mimeTypes[$fileExtension] ?? 'image/jpeg';

        $uploadResponse = Http::attach(
            'file',
            $mediaContent,
            basename($mediaUrl),
            ['Content-Type' => $mimeType]
        )->put($uploadUrl);

        if (!$uploadResponse->successful()) {
            Log::error('LinkedInService: Failed to upload image to LinkedIn', [
                'status_code' => $uploadResponse->status(),
                'response' => $uploadResponse->body(),
            ]);
            return null;
        }

        Log::info('LinkedInService: Image uploaded successfully', ['image_urn' => $imageUrn]);
        return $imageUrn;
    }

    private function uploadVideoToLinkedIn(string $mediaUrl, SocialAccount $account): ?string
    {
        // Get file size for video upload
        $mediaContent = @file_get_contents($mediaUrl);
        if ($mediaContent === false) {
            Log::error('LinkedInService: Failed to download video', ['media_url' => $mediaUrl]);
            return null;
        }

        $fileSizeBytes = strlen($mediaContent);

        // Initialize video upload
        $initializePayload = [
            'initializeUploadRequest' => [
                'owner'            => "urn:li:person:{$account->account_id}",
                'fileSizeBytes'    => $fileSizeBytes,
                'uploadCaptions'   => false,
                'uploadThumbnail'  => false,
            ],
        ];

        $initializeResponse = Http::withHeaders([
            'Authorization'             => 'Bearer ' . $this->getAccessToken($account),
            'X-Restli-Protocol-Version' => '2.0.0',
            'Content-Type'              => 'application/json',
            'LinkedIn-Version'           => '202602',
        ])->post('https://api.linkedin.com/rest/videos?action=initializeUpload', $initializePayload);

        if (!$initializeResponse->successful()) {
            Log::error('LinkedInService: Failed to initialize video upload', [
                'response'    => $initializeResponse->json(),
                'status_code' => $initializeResponse->status(),
            ]);
            return null;
        }

        $initializeData = $initializeResponse->json();
        $videoUrn = $initializeData['value']['video'] ?? null;
        $uploadInstructions = $initializeData['value']['uploadInstructions'][0] ?? null;
        $uploadUrl = $uploadInstructions['uploadUrl'] ?? null;

        if (!$videoUrn || !$uploadUrl) {
            Log::error('LinkedInService: Invalid initialize video upload response', ['response' => $initializeData]);
            return null;
        }

        Log::info('LinkedInService: Initialized video upload', [
            'video_urn' => $videoUrn,
            'upload_url' => $uploadUrl,
            'file_size_bytes' => $fileSizeBytes,
        ]);

        // Upload video file
        $fileExtension = strtolower(pathinfo($mediaUrl, PATHINFO_EXTENSION));
        $mimeTypes = [
            'mp4' => 'video/mp4', 'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo', 'mkv' => 'video/x-matroska',
        ];
        $mimeType = $mimeTypes[$fileExtension] ?? 'video/mp4';

        $uploadResponse = Http::attach(
            'file',
            $mediaContent,
            basename($mediaUrl),
            ['Content-Type' => $mimeType]
        )->put($uploadUrl);

        if (!$uploadResponse->successful()) {
            Log::error('LinkedInService: Failed to upload video to LinkedIn', [
                'status_code' => $uploadResponse->status(),
                'response' => $uploadResponse->body(),
            ]);
            return null;
        }

        Log::info('LinkedInService: Video uploaded successfully', ['video_urn' => $videoUrn]);
        return $videoUrn;
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