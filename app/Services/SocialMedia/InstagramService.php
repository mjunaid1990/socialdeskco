<?php

namespace App\Services\SocialMedia;

use App\Models\Post;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InstagramService extends BaseSocialMediaService
{
    public function __construct()
    {
        Log::info('InstagramService: CONSTRUCTOR CALLED - NEW VERSION', [
            'timestamp' => now()->toISOString(),
            'class' => __CLASS__
        ]);
    }
    
    public function publishPost(Post $post, SocialAccount $account)
    {
        Log::info('InstagramService: NEW VERSION - Starting publishPost', [
            'post_id' => $post->id,
            'account_id' => $account->id,
            'timestamp' => now()->toISOString()
        ]);
        
        try {
            Log::info('InstagramService: Publishing post', [
                'post_id' => $post->id,
                'has_media' => !empty($post->media_files),
                'media_count' => count($post->media_files ?? [])
            ]);
            
            // Debug access token and permissions first
            $this->debugAccessToken($account);
            
            // First validate the Instagram account connection
            $this->validateInstagramConnection($account);
            
            // Instagram requires media for all posts - text-only posts are not supported
            $hasMedia = $post->media_files && !empty($post->media_files);
            if (!$hasMedia) {
                $this->logApiError('Instagram requires media for posts', [
                    'post_id' => $post->id,
                    'error' => 'Instagram does not support text-only posts',
                    'suggestion' => 'Please upload an image or video with your post'
                ]);
                $this->updatePostStatus($post, 'failed', ['error' => 'Instagram requires media for posts']);
                return false;
            }

            $mediaUrl = $post->media_files[0];
            $isVideo = $this->isVideoUrl($mediaUrl);
            
            Log::info('InstagramService: Processing media', [
                'media_url' => $mediaUrl,
                'is_video' => $isVideo
            ]);

            if ($isVideo) {
                return $this->publishVideo($post, $account, $mediaUrl);
            } else {
                return $this->publishPhoto($post, $account, $mediaUrl);
            }
        } catch (\Exception $e) {
            Log::error('InstagramService: Exception in publishPost', [
                'post_id' => $post->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->handleException($post, 'Instagram', $e);
        }
    }
    
    private function validateInstagramConnection(SocialAccount $account): void
    {
        try {
            $instagramUserId = $this->getInstagramUserId($account);
            
            Log::info('InstagramService: Validating connection', [
                'account_id' => $account->id,
                'instagram_user_id' => $instagramUserId
            ]);
            
            // Test the connection by making a simple API call
            $testUrl = "https://graph.facebook.com/" . $this->getApiVersion('instagram', '19.0') . "/{$instagramUserId}";
            $response = $this->makeApiRequest('get', $testUrl, ['fields' => 'id,username'], [
                'Authorization' => 'Bearer ' . $this->getAccessToken($account)
            ]);
            
            Log::info('InstagramService: Connection test response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'response_body' => $response->json()
            ]);
            
            if (!$response->successful()) {
                $errorData = $response->json();
                throw new \Exception('Instagram connection failed: ' . ($errorData['error']['message'] ?? 'Unknown error'));
            }
            
            Log::info('InstagramService: Connection validated successfully');
            
        } catch (\Exception $e) {
            Log::error('InstagramService: Connection validation failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Instagram account connection is invalid. Please reconnect your Instagram account: ' . $e->getMessage());
        }
    }

    private function publishPhoto(Post $post, SocialAccount $account, string $imageUrl)
    {
        $creationId = null;
        
        try {
            $instagramUserId = $this->getInstagramUserId($account);
            Log::info('InstagramService: Creating photo container', [
                'post_id' => $post->id,
                'instagram_user_id' => $instagramUserId,
                'image_url' => $imageUrl
            ]);
            
            // Step 1: Create media container for image using Facebook Graph API
            $payload = [
                'caption' => $post->content,
                'image_url' => $imageUrl,
                'media_type' => 'IMAGE',
            ];
            
            $apiUrl = "https://graph.facebook.com/" . $this->getApiVersion('instagram', '19.0') . "/{$instagramUserId}/media";
            
            Log::info('InstagramService: Making API request', [
                'url' => $apiUrl,
                'payload' => $payload
            ]);
            
            $response = $this->makeApiRequest('post', $apiUrl, $payload, [
                'Authorization' => 'Bearer ' . $this->getAccessToken($account)
            ]);

            Log::info('InstagramService: API response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'response_body' => $response->json()
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                $creationId = $responseData['id'] ?? null;
                
                if ($creationId) {
                    Log::info('InstagramService: Photo container created', [
                        'creation_id' => $creationId
                    ]);
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
                return $this->handleApiError($post, 'Instagram create photo', $response, ['creation_id' => $creationId, 'instagram_user_id' => $instagramUserId], $account);
            }
        } catch (\Exception $e) {
            Log::error('InstagramService: Exception in publishPhoto', [
                'post_id' => $post->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->handleException($post, 'Instagram photo', $e);
        }
    }

    private function publishVideo(Post $post, SocialAccount $account, string $videoUrl)
    {
        $creationId = null;
        
        try {
            $instagramUserId = $this->getInstagramUserId($account);
            Log::info('InstagramService: Creating video container', [
                'post_id' => $post->id,
                'instagram_user_id' => $instagramUserId,
                'video_url' => $videoUrl
            ]);
            
            // Step 1: Create media container for video using Facebook Graph API
            $payload = [
                'caption' => $post->content,
                'video_url' => $videoUrl,
                'media_type' => 'VIDEO',
            ];
            
            $apiUrl = "https://graph.facebook.com/" . $this->getApiVersion('instagram', '19.0') . "/{$instagramUserId}/media";
            
            Log::info('InstagramService: Making video API request', [
                'url' => $apiUrl,
                'payload' => $payload
            ]);
            
            $response = $this->makeApiRequest('post', $apiUrl, $payload, [
                'Authorization' => 'Bearer ' . $this->getAccessToken($account)
            ]);

            Log::info('InstagramService: Video API response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'response_body' => $response->json()
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                $creationId = $responseData['id'] ?? null;
                
                if ($creationId) {
                    Log::info('InstagramService: Video container created', [
                        'creation_id' => $creationId
                    ]);
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
                return $this->handleApiError($post, 'Instagram create video', $response, ['creation_id' => $creationId, 'instagram_user_id' => $instagramUserId], $account);
            }
        } catch (\Exception $e) {
            Log::error('InstagramService: Exception in publishVideo', [
                'post_id' => $post->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->handleException($post, 'Instagram video', $e);
        }
    }

    private function publishMediaContainer(Post $post, SocialAccount $account, string $creationId, string $mediaType)
    {
        try {
            $instagramUserId = $this->getInstagramUserId($account);
            Log::info('InstagramService: Publishing media container', [
                'post_id' => $post->id,
                'creation_id' => $creationId,
                'instagram_user_id' => $instagramUserId,
                'media_type' => $mediaType
            ]);
            
            // Step 2: Publish the media container using Facebook Graph API
            $publishUrl = "https://graph.facebook.com/" . $this->getApiVersion('instagram', '19.0') . "/{$instagramUserId}/media_publish";
            $publishPayload = ['creation_id' => $creationId];
            
            Log::info('InstagramService: Making publish request', [
                'url' => $publishUrl,
                'payload' => $publishPayload
            ]);
            
            $publishResponse = $this->makeApiRequest('post', $publishUrl, $publishPayload, [
                'Authorization' => 'Bearer ' . $this->getAccessToken($account)]
            );

            Log::info('InstagramService: Publish response', [
                'status' => $publishResponse->status(),
                'successful' => $publishResponse->successful(),
                'response_body' => $publishResponse->json()
            ]);

            if ($publishResponse->successful()) {
                $publishData = $publishResponse->json();
                $mediaId = $publishData['id'] ?? null;
                
                Log::info('InstagramService: Post published successfully', [
                    'post_id' => $post->id,
                    'media_id' => $mediaId,
                    'media_type' => $mediaType
                ]);
                
                // Store media ID for deletion and other platform data
                $platformData = array_merge([
                    'creation_id' => $creationId,
                    'media_id' => $mediaId,
                ], $publishData);
                
                $this->updatePostStatus($post, 'published', $platformData);
                return $publishData;
            } else {
                return $this->handleApiError($post, 'Instagram publish ' . $mediaType, $publishResponse, ['creation_id' => $creationId, 'instagram_user_id' => $instagramUserId], $account);
            }
        } catch (\Exception $e) {
            Log::error('InstagramService: Exception in publishMediaContainer', [
                'post_id' => $post->id,
                'creation_id' => $creationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->handleException($post, 'Instagram publish', $e);
        }
    }

    private function waitForVideoProcessingAndPublish(Post $post, SocialAccount $account, string $creationId, int $maxAttempts = 10)
    {
        try {
            Log::info('InstagramService: Waiting for video processing', [
                'post_id' => $post->id,
                'creation_id' => $creationId,
                'max_attempts' => $maxAttempts
            ]);
            
            // Instagram videos need time to process before publishing
            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                Log::info('InstagramService: Checking video status', [
                    'attempt' => $attempt,
                    'creation_id' => $creationId
                ]);
                
                // Check container status using Facebook Graph API
                $statusUrl = "https://graph.facebook.com/" . $this->getApiVersion('instagram', '19.0') . "/{$creationId}";
                $statusResponse = $this->makeApiRequest('get', $statusUrl, ['fields' => 'status_code'], [
                    'Authorization' => 'Bearer ' . $this->getAccessToken($account)
                ]);

                Log::info('InstagramService: Video status response', [
                    'attempt' => $attempt,
                    'status' => $statusResponse->status(),
                    'response_body' => $statusResponse->json()
                ]);

                if ($statusResponse->successful()) {
                    $statusData = $statusResponse->json();
                    $statusCode = $statusData['status_code'] ?? null;

                    Log::info('InstagramService: Video processing status', [
                        'attempt' => $attempt,
                        'status_code' => $statusCode,
                        'creation_id' => $creationId
                    ]);

                    if ($statusCode === 'FINISHED') {
                        Log::info('InstagramService: Video processing finished, publishing', [
                            'creation_id' => $creationId,
                            'attempts_taken' => $attempt
                        ]);
                        return $this->publishMediaContainer($post, $account, $creationId, 'Instagram video');
                    } elseif ($statusCode === 'ERROR' || $statusCode === 'FAILED') {
                        $this->logApiError('Instagram video processing failed', [
                            'creation_id' => $creationId,
                            'status_data' => $statusData,
                            'post_id' => $post->id,
                            'attempts' => $attempt
                        ]);
                        $this->updatePostStatus($post, 'failed', $statusData);
                        return false;
                    }
                }

                // Wait before next attempt (exponential backoff)
                $waitTime = min($attempt * 2, 10); // Max 10 seconds
                Log::info('InstagramService: Waiting before next check', [
                    'attempt' => $attempt,
                    'wait_time' => $waitTime
                ]);
                sleep($waitTime);
            }

            $this->logApiError('Instagram video processing timeout', [
                'creation_id' => $creationId,
                'attempts' => $maxAttempts,
                'post_id' => $post->id
            ]);
            $this->updatePostStatus($post, 'failed', ['error' => 'Video processing timeout']);
            return false;
        } catch (\Exception $e) {
            Log::error('InstagramService: Exception in waitForVideoProcessingAndPublish', [
                'post_id' => $post->id,
                'creation_id' => $creationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->handleException($post, 'Instagram video processing', $e);
        }
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

            // Delete the media using Facebook Graph API
            $response = $this->makeApiRequest('delete',
                "https://graph.facebook.com/" . $this->getApiVersion('instagram', '22.0') . "/{$mediaId}",
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
                return $this->handleApiError($post, 'Instagram delete post', $response, ['media_id' => $mediaId], $account);
            }
        } catch (\Exception $e) {
            return $this->handleException($post, 'Instagram delete', $e);
        }
    }

    private function getInstagramUserId(SocialAccount $account): string
    {
        // Get Instagram Business ID from profile data (from Facebook Graph API)
        $profileData = $account->profile_data ?? [];
        
        Log::info('InstagramService: Getting Instagram User ID', [
            'account_id' => $account->id,
            'profile_data' => $profileData,
            'platform_data' => $account->platform_specific_data ?? [],
            'provider_id' => $account->provider_id
        ]);
        
        // First try to get the Instagram Business Account ID from the Facebook Page
        if (isset($profileData['facebook_page_id'])) {
            $facebookPageId = $profileData['facebook_page_id'];
            Log::info('InstagramService: Found Facebook Page ID, fetching Instagram Business Account', [
                'facebook_page_id' => $facebookPageId
            ]);
            
            try {
                // Use Facebook Graph API to get the Instagram Business Account ID
                $instagramBusinessId = $this->getInstagramBusinessAccountFromFacebookPage($account, $facebookPageId);
                if ($instagramBusinessId && $this->isValidInstagramBusinessId($instagramBusinessId)) {
                    Log::info('InstagramService: Successfully retrieved Instagram Business Account ID', [
                        'instagram_business_id' => $instagramBusinessId
                    ]);
                    return $instagramBusinessId;
                }
            } catch (\Exception $e) {
                Log::warning('InstagramService: Failed to get Instagram Business Account from Facebook Page', [
                    'facebook_page_id' => $facebookPageId,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Fallback to checking if the stored ID is actually an Instagram Business Account ID
        if (isset($profileData['id'])) {
            $instagramId = $profileData['id'];
            Log::info('InstagramService: Checking if stored ID is Instagram Business Account', ['id' => $instagramId]);
            
            // Validate that this looks like an Instagram Business Account ID
            if ($this->isValidInstagramBusinessId($instagramId)) {
                // Test if this ID actually works with Instagram API
                if ($this->testInstagramAccountId($account, $instagramId)) {
                    Log::info('InstagramService: Stored ID is valid Instagram Business Account', ['id' => $instagramId]);
                    return $instagramId;
                } else {
                    Log::warning('InstagramService: Stored ID failed Instagram API test', ['id' => $instagramId]);
                }
            } else {
                Log::warning('InstagramService: Stored ID is not valid Instagram Business Account format', [
                    'id' => $instagramId,
                    'length' => strlen($instagramId)
                ]);
            }
        }
        
        // Check platform specific data as fallback
        $platformData = $account->platform_specific_data ?? [];
        if (isset($platformData['instagram_business_id'])) {
            $instagramId = $platformData['instagram_business_id'];
            Log::info('InstagramService: Found ID in platform_specific_data', ['id' => $instagramId]);
            
            if ($this->isValidInstagramBusinessId($instagramId) && $this->testInstagramAccountId($account, $instagramId)) {
                return $instagramId;
            }
        }
        
        // Log error and throw exception
        Log::error('Instagram Business ID not found or invalid - account may not be properly connected', [
            'account_id' => $account->id,
            'profile_data' => $profileData,
            'platform_data' => $platformData,
            'provider_id' => $account->provider_id,
        ]);
        
        throw new \Exception('Instagram Business Account not found or not properly connected. Please ensure your Instagram account is a Business/Creator account and connected to a Facebook Page.');
    }
    
    private function getInstagramBusinessAccountFromFacebookPage(SocialAccount $account, string $facebookPageId): ?string
    {
        // First, let's debug the access token and permissions
        $this->debugAccessToken($account);
        
        $url = "https://graph.facebook.com/" . $this->getApiVersion('instagram', '19.0') . "/{$facebookPageId}";
        $response = $this->makeApiRequest('get', $url, ['fields' => 'instagram_business_account,name,username'], [
            'Authorization' => 'Bearer ' . $this->getAccessToken($account)
        ]);
        
        Log::info('InstagramService: Facebook Page Instagram Business Account response', [
            'facebook_page_id' => $facebookPageId,
            'url' => $url,
            'status' => $response->status(),
            'response_body' => $response->json()
        ]);
        
        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['instagram_business_account']['id'])) {
                return $data['instagram_business_account']['id'];
            } else {
                Log::warning('InstagramService: No instagram_business_account field in response', [
                    'response_data' => $data
                ]);
            }
        } else {
            Log::error('InstagramService: Failed to get Facebook Page data', [
                'facebook_page_id' => $facebookPageId,
                'error' => $response->json()
            ]);
        }
        
        return null;
    }
    
    private function debugAccessToken(SocialAccount $account): void
    {
        $token = $this->getAccessToken($account);
        
        Log::info('InstagramService: Access token debug info', [
            'token_length' => strlen($token),
            'token_prefix' => substr($token, 0, 20) . '...'
        ]);
        
        // Try to debug token only if we have the Facebook app credentials
        $facebookClientId = $this->getClientId('facebook');
        $facebookClientSecret = $this->getClientSecret('facebook');
        
        if ($facebookClientId && $facebookClientSecret) {
            $debugUrl = "https://graph.facebook.com/" . $this->getApiVersion('instagram', '19.0') . "/debug_token";
            $response = Http::asForm()->post($debugUrl, [
                'input_token' => $token,
                'access_token' => $facebookClientId . '|' . $facebookClientSecret
            ]);
            
            Log::info('InstagramService: Access token debug response', [
                'debug_response' => $response->json()
            ]);
        } else {
            Log::warning('InstagramService: Facebook app credentials not found, skipping token debug', [
                'facebook_client_id' => $facebookClientId,
                'has_client_secret' => !empty($facebookClientSecret)
            ]);
        }
        
        // Also try to get user info with the token
        $meUrl = "https://graph.facebook.com/" . $this->getApiVersion('instagram', '19.0') . "/me";
        $meResponse = $this->makeApiRequest('get', $meUrl, ['fields' => 'id,name,permissions'], [
            'Authorization' => 'Bearer ' . $token
        ]);
        
        Log::info('InstagramService: User info with token', [
            'me_url' => $meUrl,
            'status' => $meResponse->status(),
            'response' => $meResponse->json()
        ]);
    }
    
    private function testInstagramAccountId(SocialAccount $account, string $instagramId): bool
    {
        $testUrl = "https://graph.facebook.com/" . $this->getApiVersion('instagram', '19.0') . "/{$instagramId}";
        $response = $this->makeApiRequest('get', $testUrl, ['fields' => 'id,username'], [
            'Authorization' => 'Bearer ' . $this->getAccessToken($account)
        ]);
        
        Log::info('InstagramService: Testing Instagram Account ID', [
            'instagram_id' => $instagramId,
            'status' => $response->status(),
            'successful' => $response->successful()
        ]);
        
        return $response->successful();
    }
    
    private function isValidInstagramBusinessId(string $id): bool
    {
        // Instagram Business Account IDs are typically numeric and around 15-17 digits
        // They should not contain any special characters or letters
        return is_numeric($id) && strlen($id) >= 15 && strlen($id) <= 20;
    }

    public function refreshToken(SocialAccount $account): bool
    {
        try {
            // For Instagram Business accounts using Facebook Login, use Facebook token refresh
            $response = Http::asForm()->post('https://graph.facebook.com/oauth/access_token', [
                'grant_type' => 'fb_exchange_token',
                'client_id' => $this->getClientId('facebook'), // Use Facebook client ID
                'client_secret' => $this->getClientSecret('facebook'), // Use Facebook client secret
                'fb_exchange_token' => $this->getAccessToken($account),
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

                Log::info('Instagram token refreshed successfully via Facebook', [
                    'account_id' => $account->id,
                    'new_expires_at' => $account->token_expires_at,
                ]);

                return true;
            } else {
                Log::error('Instagram token refresh failed via Facebook', [
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
