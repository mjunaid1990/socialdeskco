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
            $this->debugAccessToken($account);
            $this->validateInstagramConnection($account);

            // FIX: Use actual media files instead of hardcoded dummy URLs
            $hasMedia = $post->media_files && !empty($post->media_files);
            $mediaType = $post->media_type ?? 'image';

            if (!$hasMedia) {
                // Instagram requires media — fail gracefully
                Log::warning('InstagramService: No media files found; Instagram requires image or video', [
                    'post_id' => $post->id,
                ]);
                $this->updatePostStatus($post, 'failed', ['error' => 'Instagram requires at least one image or video file.']);
                return false;
            }

            $mediaUrl = $post->media_files[0];
            $isVideo  = $this->isVideoUrl($mediaUrl);

            if ($isVideo) {
                return $this->publishVideo($post, $account, $mediaUrl);
            } else {
                return $this->publishPhoto($post, $account, $mediaUrl);
            }
        } catch (\Exception $e) {
            Log::error('InstagramService: Exception in publishPost', [
                'post_id' => $post->id,
                'error'   => $e->getMessage(),
            ]);
            return $this->handleException($post, 'Instagram', $e);
        }
    }

    private function validateInstagramConnection(SocialAccount $account): void
    {
        try {
            $instagramUserId = $this->getInstagramUserId($account);

            $testUrl  = "https://graph.facebook.com/" . $this->getApiVersion('instagram', 'v22.0') . "/{$instagramUserId}";
            $response = $this->makeApiRequest('get', $testUrl, ['fields' => 'id,username'], [
                'Authorization' => 'Bearer ' . $this->getAccessToken($account),
            ]);

            if (!$response->successful()) {
                $errorData = $response->json();
                throw new \Exception('Instagram connection failed: ' . ($errorData['error']['message'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            Log::error('InstagramService: Connection validation failed', [
                'account_id' => $account->id,
                'error'      => $e->getMessage(),
            ]);
            throw new \Exception('Instagram account connection is invalid. Please reconnect your Instagram account: ' . $e->getMessage());
        }
    }

    private function publishPhoto(Post $post, SocialAccount $account, string $imageUrl)
    {
        $creationId = null;

        try {
            $instagramUserId = $this->getInstagramUserId($account);
            $apiVersion      = $this->getApiVersion('instagram', 'v22.0');

            $payload = [
                'caption'    => $post->content,
                'image_url'  => $imageUrl,
                'media_type' => 'IMAGE',
            ];

            if (!empty($post->instagram_settings['alt_text'])) {
                $payload['alt_text'] = $post->instagram_settings['alt_text'];
            }

            $apiUrl  = "https://graph.facebook.com/{$apiVersion}/{$instagramUserId}/media";
            $response = $this->makeApiRequest('post', $apiUrl, $payload, [
                'Authorization' => 'Bearer ' . $this->getAccessToken($account),
            ]);

            if ($response->successful()) {
                $data       = $response->json();
                $creationId = $data['id'] ?? null;

                if (!$creationId) {
                    $this->updatePostStatus($post, 'failed', ['error' => 'No creation ID returned']);
                    return false;
                }

                return $this->publishMediaContainer($post, $account, $creationId, 'Instagram photo');
            } else {
                return $this->handleApiError($post, 'Instagram create photo', $response, ['creation_id' => $creationId], $account);
            }
        } catch (\Exception $e) {
            Log::error('InstagramService: Exception in publishPhoto', [
                'post_id'     => $post->id,
                'creation_id' => $creationId,
                'error'       => $e->getMessage(),
            ]);
            return $this->handleException($post, 'Instagram', $e);
        }
    }

    private function publishVideo(Post $post, SocialAccount $account, string $videoUrl)
    {
        $creationId = null;

        try {
            $instagramUserId = $this->getInstagramUserId($account);
            $apiVersion      = $this->getApiVersion('instagram', 'v22.0');

            $payload = [
                'caption'    => $post->content,
                'video_url'  => $videoUrl,
                'media_type' => 'REELS', // FIX: Use REELS for video on Instagram (VIDEO deprecated)
            ];

            $apiUrl   = "https://graph.facebook.com/{$apiVersion}/{$instagramUserId}/media";
            $response = $this->makeApiRequest('post', $apiUrl, $payload, [
                'Authorization' => 'Bearer ' . $this->getAccessToken($account),
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                $creationId   = $responseData['id'] ?? null;

                if ($creationId) {
                    return $this->waitForVideoProcessingAndPublish($post, $account, $creationId);
                } else {
                    $this->updatePostStatus($post, 'failed', $responseData);
                    return false;
                }
            } else {
                return $this->handleApiError($post, 'Instagram create video', $response, ['instagram_user_id' => $instagramUserId], $account);
            }
        } catch (\Exception $e) {
            Log::error('InstagramService: Exception in publishVideo', [
                'post_id' => $post->id,
                'error'   => $e->getMessage(),
            ]);
            return $this->handleException($post, 'Instagram video', $e);
        }
    }

    private function publishMediaContainer(Post $post, SocialAccount $account, string $creationId, string $mediaType)
    {
        try {
            $instagramUserId = $this->getInstagramUserId($account);
            $apiVersion      = $this->getApiVersion('instagram', 'v22.0');

            $publishUrl      = "https://graph.facebook.com/{$apiVersion}/{$instagramUserId}/media_publish";
            $publishPayload  = ['creation_id' => $creationId];

            $publishResponse = $this->makeApiRequest('post', $publishUrl, $publishPayload, [
                'Authorization' => 'Bearer ' . $this->getAccessToken($account),
            ]);

            if ($publishResponse->successful()) {
                $publishData = $publishResponse->json();
                $mediaId     = $publishData['id'] ?? null;

                // FIX: Store media_id at top level for easy retrieval during deletion
                $platformData = [
                    'creation_id' => $creationId,
                    'media_id'    => $mediaId,
                    'id'          => $mediaId, // alias for consistency
                ];

                $this->updatePostStatus($post, 'published', $platformData);
                return true;
            } else {
                return $this->handleApiError($post, 'Instagram publish ' . $mediaType, $publishResponse, ['creation_id' => $creationId], $account);
            }
        } catch (\Exception $e) {
            Log::error('InstagramService: Exception in publishMediaContainer', [
                'post_id'     => $post->id,
                'creation_id' => $creationId,
                'error'       => $e->getMessage(),
            ]);
            return $this->handleException($post, 'Instagram publish', $e);
        }
    }

    private function waitForVideoProcessingAndPublish(Post $post, SocialAccount $account, string $creationId, int $maxAttempts = 10)
    {
        try {
            $apiVersion = $this->getApiVersion('instagram', 'v22.0');

            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                $statusUrl      = "https://graph.facebook.com/{$apiVersion}/{$creationId}";
                $statusResponse = $this->makeApiRequest('get', $statusUrl, ['fields' => 'status_code'], [
                    'Authorization' => 'Bearer ' . $this->getAccessToken($account),
                ]);

                if ($statusResponse->successful()) {
                    $statusData = $statusResponse->json();
                    $statusCode = $statusData['status_code'] ?? null;

                    if ($statusCode === 'FINISHED') {
                        return $this->publishMediaContainer($post, $account, $creationId, 'Instagram video');
                    } elseif ($statusCode === 'ERROR' || $statusCode === 'FAILED') {
                        $this->updatePostStatus($post, 'failed', $statusData);
                        return false;
                    }
                }

                sleep(min($attempt * 2, 10));
            }

            $this->updatePostStatus($post, 'failed', ['error' => 'Video processing timeout']);
            return false;
        } catch (\Exception $e) {
            return $this->handleException($post, 'Instagram video processing', $e);
        }
    }

    private function isVideoUrl(string $url): bool
    {
        $videoExtensions = ['mp4', 'mov', 'avi', 'wmv', 'flv', 'webm', 'mkv'];
        $extension       = strtolower(pathinfo($url, PATHINFO_EXTENSION));
        return in_array($extension, $videoExtensions);
    }

    public function deletePost(Post $post, SocialAccount $account): bool
    {
        try {
            $platformData = $post->platform_specific_data ?? [];
            $mediaId      = $platformData['media_id'] ?? $platformData['id'] ?? null;

            if (!$mediaId) {
                $this->logApiError('Cannot delete Instagram post: No media ID', [
                    'post_id'       => $post->id,
                    'platform_data' => $platformData,
                ]);
                return false;
            }

            $apiVersion = $this->getApiVersion('instagram', 'v22.0');
            $response   = $this->makeApiRequest('delete',
                "https://graph.facebook.com/{$apiVersion}/{$mediaId}",
                [],
                ['Authorization' => 'Bearer ' . $this->getAccessToken($account)]
            );

            if ($response->successful()) {
                $responseData = $response->json();

                if ($responseData['success'] ?? false) {
                    $this->updatePostStatus($post, 'deleted', $responseData);
                    return true;
                } else {
                    $this->logApiError('Instagram post deletion failed', [
                        'post_id'  => $post->id,
                        'media_id' => $mediaId,
                        'response' => $responseData,
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
        $profileData = $account->profile_data ?? [];

        if (isset($profileData['facebook_page_id'])) {
            try {
                $instagramBusinessId = $this->getInstagramBusinessAccountFromFacebookPage($account, $profileData['facebook_page_id']);
                if ($instagramBusinessId && $this->isValidInstagramBusinessId($instagramBusinessId)) {
                    return $instagramBusinessId;
                }
            } catch (\Exception $e) {
                Log::warning('InstagramService: Failed to get Instagram Business Account from Facebook Page', [
                    'facebook_page_id' => $profileData['facebook_page_id'],
                    'error'            => $e->getMessage(),
                ]);
            }
        }

        if (isset($profileData['id']) && $this->isValidInstagramBusinessId($profileData['id'])) {
            if ($this->testInstagramAccountId($account, $profileData['id'])) {
                return $profileData['id'];
            }
        }

        $platformData = $account->platform_specific_data ?? [];
        if (isset($platformData['instagram_business_id'])) {
            $id = $platformData['instagram_business_id'];
            if ($this->isValidInstagramBusinessId($id) && $this->testInstagramAccountId($account, $id)) {
                return $id;
            }
        }

        throw new \Exception('Instagram Business Account not found or not properly connected. Please ensure your Instagram account is a Business/Creator account and connected to a Facebook Page.');
    }

    private function getInstagramBusinessAccountFromFacebookPage(SocialAccount $account, string $facebookPageId): ?string
    {
        $apiVersion = $this->getApiVersion('instagram', 'v22.0');
        $url        = "https://graph.facebook.com/{$apiVersion}/{$facebookPageId}";
        $response   = $this->makeApiRequest('get', $url, ['fields' => 'instagram_business_account,name'], [
            'Authorization' => 'Bearer ' . $this->getAccessToken($account),
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['instagram_business_account']['id'] ?? null;
        }

        return null;
    }

    private function debugAccessToken(SocialAccount $account): void
    {
        $token = $this->getAccessToken($account);

        Log::info('InstagramService: Access token debug info', [
            'token_length' => strlen($token),
            'token_prefix' => substr($token, 0, 20) . '...',
        ]);
    }

    private function testInstagramAccountId(SocialAccount $account, string $instagramId): bool
    {
        $apiVersion = $this->getApiVersion('instagram', 'v22.0');
        $testUrl    = "https://graph.facebook.com/{$apiVersion}/{$instagramId}";
        $response   = $this->makeApiRequest('get', $testUrl, ['fields' => 'id,username'], [
            'Authorization' => 'Bearer ' . $this->getAccessToken($account),
        ]);

        return $response->successful();
    }

    private function isValidInstagramBusinessId(string $id): bool
    {
        return is_numeric($id) && strlen($id) >= 15 && strlen($id) <= 20;
    }

    public function refreshToken(SocialAccount $account): bool
    {
        try {
            $response = Http::asForm()->post('https://graph.facebook.com/oauth/access_token', [
                'grant_type'      => 'fb_exchange_token',
                'client_id'       => $this->getClientId('facebook'),
                'client_secret'   => $this->getClientSecret('facebook'),
                'fb_exchange_token' => $this->getAccessToken($account),
            ]);

            if ($response->successful()) {
                $this->updateAccountTokens($account, $response->json());
                return true;
            }

            Log::error('Instagram token refresh failed', [
                'status_code' => $response->status(),
                'response'    => $response->json(),
                'account_id'  => $account->id,
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('Instagram token refresh exception', [
                'error'      => $e->getMessage(),
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