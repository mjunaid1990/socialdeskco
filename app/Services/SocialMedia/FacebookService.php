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
                return $this->publishText($post, $account);
            }
        } catch (\Exception $e) {
            return $this->handleException($post, 'Facebook', $e);
        }
    }

    private function publishText(Post $post, SocialAccount $account)
    {
        $pageId = $this->getPageId($account);
        $pageAccessToken = $this->getPageAccessToken($account);
        $apiVersion = '22.0';

        Log::info('Facebook attempting text post', [
            'page_id' => $pageId,
            'api_version' => $apiVersion,
        ]);

        $payload = [
            'message' => $post->content,
            'privacy' => [
                'value' => $post->facebook_settings['privacy'] ?? 'PUBLIC'
            ],
        ];

        $response = $this->makeApiRequest('post',
            "https://graph.facebook.com/v{$apiVersion}/{$pageId}/feed",
            $payload,
            ['Authorization' => 'Bearer ' . $pageAccessToken]
        );

        if ($response->successful()) {
            return $this->handleApiSuccess($post, $response);
        } else {
            return $this->handleApiError($post, 'Facebook', $response);
        }
    }

    private function publishPhoto(Post $post, SocialAccount $account, string $imageUrl)
    {
        $pageId = $this->getPageId($account);
        $pageAccessToken = $this->getPageAccessToken($account);
        $apiVersion = '22.0';

        // FIX: Use /photos endpoint with url parameter for real image upload
        // The image URL must be publicly accessible for Facebook to fetch it
        $payload = [
            'url'     => $imageUrl,
            'caption' => $post->content,
            'privacy' => [
                'value' => $post->facebook_settings['privacy'] ?? 'PUBLIC'
            ],
        ];

        Log::info('Facebook attempting photo post', [
            'page_id'   => $pageId,
            'image_url' => $imageUrl,
        ]);

        $response = $this->makeApiRequest('post',
            "https://graph.facebook.com/v{$apiVersion}/{$pageId}/photos",
            $payload,
            ['Authorization' => 'Bearer ' . $pageAccessToken]
        );

        Log::info('Facebook photo post response', [
            'status'   => $response->status(),
            'response' => $response->json(),
        ]);

        if ($response->successful()) {
            return $this->handleApiSuccess($post, $response);
        } else {
            // Fallback: post as text-only if photo upload fails (e.g. localhost URL)
            Log::warning('Facebook photo upload failed, falling back to text post', [
                'error' => $response->json(),
            ]);
            return $this->publishText($post, $account);
        }
    }

    private function publishVideo(Post $post, SocialAccount $account, string $videoUrl)
    {
        $pageId = $this->getPageId($account);
        $pageAccessToken = $this->getPageAccessToken($account);

        $payload = [
            'file_url'    => $videoUrl,
            'description' => $post->content,
            'published'   => true,
            'privacy'     => [
                'value' => $post->facebook_settings['privacy'] ?? 'PUBLIC'
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
        $profileData = $account->profile_data ?? [];

        if (isset($profileData['accounts']['data'][0]['id'])) {
            return $profileData['accounts']['data'][0]['id'];
        }

        if (isset($profileData['page_id'])) {
            return $profileData['page_id'];
        }

        if (isset($profileData['id'])) {
            Log::warning('Using user ID instead of page ID for Facebook', [
                'account_id' => $account->account_id,
            ]);
            return $profileData['id'];
        }

        Log::warning('Using account_id as fallback for Facebook page ID', [
            'account_id' => $account->account_id,
        ]);

        return $account->account_id;
    }

    private function getPageAccessToken(SocialAccount $account): string
    {
        $profileData = $account->profile_data ?? [];

        if (isset($profileData['accounts']['data'][0]['access_token'])) {
            return $profileData['accounts']['data'][0]['access_token'];
        }

        Log::warning('Using user access token instead of page access token for Facebook', [
            'account_id' => $account->account_id,
        ]);

        return $this->getAccessToken($account);
    }

    public function deletePost(Post $post, SocialAccount $account)
    {
        try {
            $platformData = $post->platform_specific_data ?? [];

            // FIX: support both storage formats
            $facebookPostId = $platformData['results'][$account->id]['id']
                ?? $platformData['id']
                ?? null;

            if (!$facebookPostId) {
                Log::warning('No Facebook post ID found for deletion', [
                    'post_id'          => $post->id,
                    'account_id'       => $account->id,
                    'platform_data'    => $platformData,
                ]);
                return false;
            }

            $pageAccessToken = $this->getPageAccessToken($account);

            $response = $this->makeApiRequest('delete',
                "https://graph.facebook.com/v22.0/{$facebookPostId}",
                [],
                ['Authorization' => 'Bearer ' . $pageAccessToken]
            );

            if ($response->successful()) {
                Log::info('Facebook post deleted successfully', [
                    'post_id'          => $post->id,
                    'facebook_post_id' => $facebookPostId,
                ]);
                return true;
            } else {
                Log::error('Failed to delete Facebook post', [
                    'post_id'          => $post->id,
                    'facebook_post_id' => $facebookPostId,
                    'response'         => $response->json(),
                    'status_code'      => $response->status(),
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception when deleting Facebook post', [
                'post_id'    => $post->id,
                'account_id' => $account->id,
                'error'      => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function refreshToken(SocialAccount $account): bool
    {
        if (!$this->getRefreshToken($account)) {
            return false;
        }

        $response = \Illuminate\Support\Facades\Http::asForm()->post('https://graph.facebook.com/oauth/access_token', [
            'grant_type'      => 'fb_exchange_token',
            'client_id'       => $this->getClientId('facebook'),
            'client_secret'   => $this->getClientSecret('facebook'),
            'fb_exchange_token' => $this->getRefreshToken($account),
        ]);

        if ($response->successful()) {
            $this->updateAccountTokens($account, $response->json());
            return true;
        }

        $this->logApiError('Facebook token refresh failed', [
            'status_code' => $response->status(),
            'response'    => $response->json(),
            'account_id'  => $account->id,
        ]);

        return false;
    }

    public function getPlatformName(): string
    {
        return 'facebook';
    }
}