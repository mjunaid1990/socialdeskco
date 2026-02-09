<?php

namespace App\Services\SocialMedia;

use App\Models\Post;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Log;

class FacebookService extends BaseSocialMediaService
{
    public function publishPost(Post $post, SocialAccount $account)
    {
        try {
            // Check if media files exist and determine type
            $hasMedia = $post->media_files && !empty($post->media_files);
            $mediaUrl = $hasMedia ? $post->media_files[0] : null;
            $isVideo = $mediaUrl && $this->isVideoUrl($mediaUrl);

            if ($hasMedia) {
                if ($isVideo) {
                    return $this->publishVideo($post, $account, $mediaUrl);
                } else {
                    return $this->publishPhoto($post, $account, $mediaUrl);
                }
            } else {
                // Text-only post
                return $this->publishText($post, $account);
            }
        } catch (\Exception $e) {
            return $this->handleException($post, 'Facebook', $e);
        }
    }

    private function publishText(Post $post, SocialAccount $account)
    {
        // Get the correct page ID and access token from profile data
        $pageId = $this->getPageId($account);
        $pageAccessToken = $this->getPageAccessToken($account);
        
        // Use v22.0 as you confirmed it works fine elsewhere
        $apiVersion = '22.0';
        
        Log::info('Facebook attempting to post', [
            'page_id' => $pageId,
            'api_version' => $apiVersion,
            'token_length' => strlen($pageAccessToken)
        ]);
        
        $payload = [
            'message' => $post->content,
            'privacy' => [
                'value' => $post->facebook_settings['privacy'] ?? 'public'
            ],
        ];
        
        $response = $this->makeApiRequest('post', 
            "https://graph.facebook.com/v{$apiVersion}/{$pageId}/feed",
            $payload,
            ['Authorization' => 'Bearer ' . $pageAccessToken]
        );

        Log::info('Facebook post response', [
            'response_status' => $response->status(),
            'response_body' => $response->json()
        ]);

        if ($response->successful()) {
            return $this->handleApiSuccess($post, $response);
        } else {
            return $this->handleApiError($post, 'Facebook', $response);
        }
    }

    private function publishPhoto(Post $post, SocialAccount $account, string $imageUrl)
    {
        // For now, let's treat photos as text posts since Facebook doesn't accept localhost URLs
        // In production, you'll need to upload the image to a public URL first
        Log::info('Facebook photo post - treating as text post due to localhost URL', [
            'image_url' => $imageUrl,
            'note' => 'Facebook requires publicly accessible URLs for images'
        ]);
        
        // Post as text with image info in the message
        $pageId = $this->getPageId($account);
        $pageAccessToken = $this->getPageAccessToken($account);
        $apiVersion = '22.0';
        
        $payload = [
            'message' => $post->content . "\n\n[Image: " . basename($imageUrl) . "]",
            'privacy' => [
                'value' => $post->facebook_settings['privacy'] ?? 'public'
            ],
        ];
        
        $response = $this->makeApiRequest('post', 
            "https://graph.facebook.com/v{$apiVersion}/{$pageId}/feed",
            $payload,
            ['Authorization' => 'Bearer ' . $pageAccessToken]
        );

        Log::info('Facebook photo-as-text post response', [
            'response_status' => $response->status(),
            'response_body' => $response->json()
        ]);

        if ($response->successful()) {
            return $this->handleApiSuccess($post, $response);
        } else {
            return $this->handleApiError($post, 'Facebook', $response);
        }
    }

    private function publishVideo(Post $post, SocialAccount $account, string $videoUrl)
    {
        // Get the correct page ID and access token from profile data
        $pageId = $this->getPageId($account);
        $pageAccessToken = $this->getPageAccessToken($account);
        
        // For videos, use the dedicated videos endpoint
        $payload = [
            'file_url' => $videoUrl,
            'description' => $post->content,
            'published' => true,
            'privacy' => [
                'value' => $post->facebook_settings['privacy'] ?? 'public'
            ],
        ];
        
        $response = $this->makeApiRequest('post', 
            "https://graph.facebook.com/v22.0/{$pageId}/videos",
            $payload,
            ['Authorization' => 'Bearer ' . $pageAccessToken]
        );

        if ($response->successful()) {
            return $this->handleApiSuccess($post, $response);
        } else {
            return $this->handleApiError($post, 'Facebook', $response);
        }
    }

    private function isVideoUrl(string $url): bool
    {
        $videoExtensions = ['mp4', 'mov', 'avi', 'wmv', 'flv', 'webm', 'mkv'];
        $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
        return in_array($extension, $videoExtensions);
    }

    private function getPageId(SocialAccount $account): string
    {
        // Check if profile_data contains page information
        $profileData = $account->profile_data ?? [];
        
        // For Facebook Pages, the page ID should be in accounts.data[0].id
        if (isset($profileData['accounts']['data'][0]['id'])) {
            $pageId = $profileData['accounts']['data'][0]['id'];
            Log::info('Using Facebook page ID from accounts data', [
                'page_id' => $pageId,
                'page_name' => $profileData['accounts']['data'][0]['name'] ?? 'Unknown'
            ]);
            return $pageId;
        }
        
        // Fallback to profile_data.id (this is usually the user ID, not page ID)
        if (isset($profileData['id'])) {
            Log::warning('Using user ID instead of page ID for Facebook', [
                'account_id' => $account->account_id,
                'profile_data' => $profileData
            ]);
            return $profileData['id'];
        }
        
        // If not found, try to get it from account_name or use account_id as fallback
        if (isset($profileData['page_id'])) {
            return $profileData['page_id'];
        }
        
        // Log warning and use account_id as fallback
        Log::warning('Using account_id as fallback for Facebook page ID', [
            'account_id' => $account->account_id,
            'profile_data' => $profileData
        ]);
        
        return $account->account_id;
    }

    private function getPageAccessToken(SocialAccount $account): string
    {
        // Check if profile_data contains page-specific access token
        $profileData = $account->profile_data ?? [];
        
        // For Facebook Pages, the page access token should be in accounts.data[0].access_token
        if (isset($profileData['accounts']['data'][0]['access_token'])) {
            $pageAccessToken = $profileData['accounts']['data'][0]['access_token'];
            Log::info('Using Facebook page access token from accounts data', [
                'page_id' => $profileData['accounts']['data'][0]['id'] ?? 'Unknown'
            ]);
            return $pageAccessToken;
        }
        
        // Fallback to user access token
        Log::warning('Using user access token instead of page access token for Facebook', [
            'account_id' => $account->account_id
        ]);
        return $this->getAccessToken($account);
    }

    public function deletePost(Post $post, SocialAccount $account)
    {
        try {
            // Get the platform-specific post ID from the post's platform data
            $platformData = $post->platform_specific_data ?? [];
            
            // Facebook post ID is stored in nested structure: results[account_id][id]
            $facebookPostId = null;
            if (isset($platformData['results'][$account->id]['id'])) {
                $facebookPostId = $platformData['results'][$account->id]['id'];
            }
            
            if (!$facebookPostId) {
                \Log::warning('No Facebook post ID found for deletion', [
                    'post_id' => $post->id,
                    'account_id' => $account->id,
                    'platform_data' => $platformData,
                    'expected_structure' => 'platform_data.results[account_id].id'
                ]);
                return false;
            }
            
            // Get page access token for deletion
            $pageAccessToken = $this->getPageAccessToken($account);
            
            \Log::info('Attempting to delete Facebook post', [
                'post_id' => $post->id,
                'facebook_post_id' => $facebookPostId,
                'page_id' => $this->getPageId($account)
            ]);
            
            // Delete the post from Facebook using page access token
            $response = $this->makeApiRequest('delete', 
                "https://graph.facebook.com/v22.0/{$facebookPostId}",
                [],
                ['Authorization' => 'Bearer ' . $pageAccessToken]
            );
            
            if ($response->successful()) {
                \Log::info('Facebook post deleted successfully', [
                    'post_id' => $post->id,
                    'facebook_post_id' => $facebookPostId,
                    'account_id' => $account->id
                ]);
                return true;
            } else {
                \Log::error('Failed to delete Facebook post', [
                    'post_id' => $post->id,
                    'facebook_post_id' => $facebookPostId,
                    'account_id' => $account->id,
                    'response' => $response->json(),
                    'status_code' => $response->status()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            \Log::error('Exception when deleting Facebook post', [
                'post_id' => $post->id,
                'account_id' => $account->id,
                'error' => $e->getMessage(),
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

        $response = Http::asForm()->post('https://graph.facebook.com/oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $this->getClientId('facebook'),
            'client_secret' => $this->getClientSecret('facebook'),
            'fb_exchange_token' => $this->getRefreshToken($account),
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $this->updateAccountTokens($account, $data);
            return true;
        } else {
            $this->logApiError('Facebook token refresh failed', [
                'status_code' => $response->status(),
                'response' => $response->json(),
                'account_id' => $account->id,
            ]);
            return false;
        }
    }

    public function getPlatformName(): string
    {
        return 'facebook';
    }
}
