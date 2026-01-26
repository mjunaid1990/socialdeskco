<?php

namespace App\Services\SocialMedia;

use App\Models\Post;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InstagramService extends BaseSocialMediaService
{
    public function publishPost(Post $post, SocialAccount $account)
    {
        try {
            // Instagram requires media for all posts - text-only posts are not supported
            $hasMedia = $post->media_files && !empty($post->media_files);
            if (!$hasMedia) {
                $this->logApiError('Instagram requires media for posts', [
                    'post_id' => $post->id,
                    'error' => 'Instagram does not support text-only posts'
                ]);
                $this->updatePostStatus($post, 'failed', ['error' => 'Instagram requires media for posts']);
                return false;
            }

            $mediaUrl = $post->media_files[0];
            $isVideo = $this->isVideoUrl($mediaUrl);

            if ($isVideo) {
                return $this->publishVideo($post, $account, $mediaUrl);
            } else {
                return $this->publishPhoto($post, $account, $mediaUrl);
            }
        } catch (\Exception $e) {
            // Check if it's a token expiration error and try to refresh
            if ($this->isTokenExpiredError($e)) {
                if ($this->refreshToken($account)) {
                    // Retry the post after successful token refresh
                    return $this->publishPost($post, $account);
                }
            }
            return $this->handleException($post, 'Instagram', $e);
        }
    }

    private function publishPhoto(Post $post, SocialAccount $account, string $imageUrl)
    {
        // Step 1: Create media container for image
        $payload = [
            'caption' => $post->content,
            'image_url' => $imageUrl,
            'media_type' => 'IMAGE',
        ];
        
        $response = $this->makeApiRequest('post',
            "https://graph.instagram.com/" . $this->getApiVersion('instagram', '22.0') . "/me/media",
            $payload,
            ['Authorization' => 'Bearer ' . $this->getAccessToken($account)]
        );

        if ($response->successful()) {
            $responseData = $response->json();
            $creationId = $responseData['id'] ?? null;
            
            if ($creationId) {
                return $this->publishMediaContainer($post, $account, $creationId, 'Instagram photo');
            } else {
                $this->logApiError('Instagram photo creation ID not returned', [
                    'response' => $responseData,
                    'post_id' => $post->id
                ]);
                $this->updatePostStatus($post, 'failed', $responseData);
                return false;
            }
        } else {
            return $this->handleApiError($post, 'Instagram create photo', $response);
        }
    }

    private function publishVideo(Post $post, SocialAccount $account, string $videoUrl)
    {
        // Step 1: Create media container for video
        $payload = [
            'caption' => $post->content,
            'video_url' => $videoUrl,
            'media_type' => 'VIDEO',
        ];
        
        $response = $this->makeApiRequest('post',
            "https://graph.instagram.com/" . $this->getApiVersion('instagram', '22.0') . "/me/media",
            $payload,
            ['Authorization' => 'Bearer ' . $this->getAccessToken($account)]
        );

        if ($response->successful()) {
            $responseData = $response->json();
            $creationId = $responseData['id'] ?? null;
            
            if ($creationId) {
                // For videos, we need to wait for processing to complete
                return $this->waitForVideoProcessingAndPublish($post, $account, $creationId);
            } else {
                $this->logApiError('Instagram video creation ID not returned', [
                    'response' => $responseData,
                    'post_id' => $post->id
                ]);
                $this->updatePostStatus($post, 'failed', $responseData);
                return false;
            }
        } else {
            return $this->handleApiError($post, 'Instagram create video', $response);
        }
    }

    private function publishMediaContainer(Post $post, SocialAccount $account, string $creationId, string $mediaType)
    {
        // Step 2: Publish the media container
        $publishResponse = $this->makeApiRequest('post',
            "https://graph.instagram.com/" . $this->getApiVersion('instagram', '22.0') . "/me/media_publish",
            ['creation_id' => $creationId],
            ['Authorization' => 'Bearer ' . $this->getAccessToken($account)]
        );

        if ($publishResponse->successful()) {
            $publishData = $publishResponse->json();
            $mediaId = $publishData['id'] ?? null;
            
            // Store media ID for deletion and other platform data
            $platformData = array_merge([
                'creation_id' => $creationId,
                'media_id' => $mediaId,
            ], $publishData);
            
            $this->updatePostStatus($post, 'published', $platformData);
            return $publishData;
        } else {
            return $this->handleApiError($post, 'Instagram publish ' . $mediaType, $publishResponse, ['creation_id' => $creationId]);
        }
    }

    private function waitForVideoProcessingAndPublish(Post $post, SocialAccount $account, string $creationId, int $maxAttempts = 10)
    {
        // Instagram videos need time to process before publishing
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            // Check container status
            $statusResponse = $this->makeApiRequest('get',
                "https://graph.instagram.com/" . $this->getApiVersion('instagram', '22.0') . "/{$creationId}",
                ['fields' => 'status_code'],
                ['Authorization' => 'Bearer ' . $this->getAccessToken($account)]
            );

            if ($statusResponse->successful()) {
                $statusData = $statusResponse->json();
                $statusCode = $statusData['status_code'] ?? null;

                if ($statusCode === 'FINISHED') {
                    return $this->publishMediaContainer($post, $account, $creationId, 'Instagram video');
                } elseif ($statusCode === 'ERROR' || $statusCode === 'FAILED') {
                    $this->logApiError('Instagram video processing failed', [
                        'creation_id' => $creationId,
                        'status_data' => $statusData,
                        'post_id' => $post->id
                    ]);
                    $this->updatePostStatus($post, 'failed', $statusData);
                    return false;
                }
            }

            // Wait before next attempt (exponential backoff)
            $waitTime = min($attempt * 2, 10); // Max 10 seconds
            sleep($waitTime);
        }

        $this->logApiError('Instagram video processing timeout', [
            'creation_id' => $creationId,
            'attempts' => $maxAttempts,
            'post_id' => $post->id
        ]);
        $this->updatePostStatus($post, 'failed', ['error' => 'Video processing timeout']);
        return false;
    }

    private function isVideoUrl(string $url): bool
    {
        $videoExtensions = ['mp4', 'mov', 'avi', 'wmv', 'flv', 'webm', 'mkv'];
        $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
        return in_array($extension, $videoExtensions);
    }

    public function deletePost(Post $post, SocialAccount $account): bool
    {
        try {
            // Get media ID from platform specific data
            $platformData = $post->platform_specific_data ?? [];
            $mediaId = $platformData['media_id'] ?? null;
            
            if (!$mediaId) {
                $this->logApiError('Cannot delete Instagram post: No media ID', [
                    'post_id' => $post->id,
                    'platform_data' => $platformData,
                    'error' => 'Media ID is required for deletion'
                ]);
                return false;
            }

            // Delete the media using Instagram Graph API
            $response = $this->makeApiRequest('delete',
                "https://graph.instagram.com/" . $this->getApiVersion('instagram', '22.0') . "/{$mediaId}",
                [],
                ['Authorization' => 'Bearer ' . $this->getAccessToken($account)]
            );

            if ($response->successful()) {
                $responseData = $response->json();
                
                // Instagram returns {"success": true} on successful deletion
                if ($responseData['success'] ?? false) {
                    $this->updatePostStatus($post, 'deleted', $responseData);
                    return true;
                } else {
                    $this->logApiError('Instagram post deletion failed', [
                        'post_id' => $post->id,
                        'media_id' => $mediaId,
                        'response' => $responseData
                    ]);
                    return false;
                }
            } else {
                return $this->handleApiError($post, 'Instagram delete post', $response);
            }
        } catch (\Exception $e) {
            return $this->handleException($post, 'Instagram delete', $e);
        }
    }

    private function isTokenExpiredError(\Exception $e): bool
    {
        $message = $e->getMessage();
        
        // Check for common token expiration error messages
        $tokenExpiredPatterns = [
            'Error validating access token',
            'Session has expired',
            'Invalid OAuth access token',
            'The access token expired',
            'token has expired',
        ];
        
        foreach ($tokenExpiredPatterns as $pattern) {
            if (stripos($message, $pattern) !== false) {
                return true;
            }
        }
        
        // Also check response data if available
        if (method_exists($e, 'getResponse') && $e->getResponse()) {
            $responseData = json_decode($e->getResponse()->getBody()->getContents(), true);
            if (isset($responseData['error']['message']) && 
                stripos($responseData['error']['message'], 'access token') !== false) {
                return true;
            }
        }
        
        return false;
    }

    public function refreshToken(SocialAccount $account): bool
    {
        try {
            // Instagram token refresh uses GET request with query parameters
            $response = Http::get("https://graph.instagram.com/" . $this->getApiVersion('instagram', '22.0') . "/refresh_access_token", [
                'grant_type' => 'ig_refresh_token',
                'access_token' => $this->getAccessToken($account),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Update the account with new tokens
                $account->update([
                    'access_token' => $data['access_token'] ?? $account->access_token,
                    'refresh_token' => $data['refresh_token'] ?? $account->refresh_token,
                    'token_expires_at' => isset($data['expires_in']) 
                        ? now()->addSeconds($data['expires_in']) 
                        : $account->token_expires_at,
                ]);

                Log::info('Instagram token refreshed successfully', [
                    'account_id' => $account->id,
                    'new_expires_at' => $account->token_expires_at,
                ]);

                return true;
            } else {
                Log::error('Instagram token refresh failed', [
                    'status_code' => $response->status(),
                    'response' => $response->json(),
                    'account_id' => $account->id,
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Instagram token refresh exception', [
                'error' => $e->getMessage(),
                'account_id' => $account->id,
            ]);
            return false;
        }
    }

    public function getPlatformName(): string
    {
        return 'instagram';
    }
}
