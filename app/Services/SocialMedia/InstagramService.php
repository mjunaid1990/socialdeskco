<?php

namespace App\Services\SocialMedia;

use App\Models\Post;
use App\Models\SocialAccount;

class InstagramService extends BaseSocialMediaService
{
    public function publishPost(Post $post, SocialAccount $account)
    {
        try {
            $imageUrl = $post->media_files ? $post->media_files[0] : null;
            
            $payload = [
                'caption' => $post->content,
            ];
            
            if ($imageUrl) {
                $payload['image_url'] = $imageUrl;
            }
            
            $response = $this->makeApiRequest('post',
                "https://graph.instagram.com/v" . $this->getApiVersion('instagram', '18.0') . "/me/media",
                $payload,
                ['Authorization' => 'Bearer ' . $this->getAccessToken($account)]
            );

            if ($response->successful()) {
                $responseData = $response->json();
                $creationId = $responseData['id'] ?? null;
                
                if ($creationId) {
                    $publishResponse = $this->makeApiRequest('post',
                        "https://graph.instagram.com/v" . $this->getApiVersion('instagram', '18.0') . "/me/media_publish",
                        ['creation_id' => $creationId],
                        ['Authorization' => 'Bearer ' . $this->getAccessToken($account)]
                    );

                    if ($publishResponse->successful()) {
                        $publishData = $publishResponse->json();
                        $this->updatePostStatus($post, 'published', array_merge($responseData, $publishData));
                        return $publishData;
                    } else {
                        return $this->handleApiError($post, 'Instagram publish', $publishResponse, ['creation_id' => $creationId]);
                    }
                } else {
                    $this->logApiError('Instagram media creation ID not returned', [
                        'response' => $responseData,
                        'post_id' => $post->id
                    ]);
                    
                    $this->updatePostStatus($post, 'failed', $responseData);
                    return false;
                }
            } else {
                return $this->handleApiError($post, 'Instagram create media', $response);
            }
        } catch (\Exception $e) {
            return $this->handleException($post, 'Instagram', $e);
        }
    }

    public function refreshToken(SocialAccount $account): bool
    {
        // Instagram uses the same token refresh mechanism as Facebook
        return (new FacebookService())->refreshToken($account);
    }

    public function getPlatformName(): string
    {
        return 'instagram';
    }
}
