<?php

namespace App\Services\SocialMedia;

use App\Models\Post;
use App\Models\SocialAccount;

class FacebookService extends BaseSocialMediaService
{
    public function publishPost(Post $post, SocialAccount $account)
    {
        try {
            $payload = [
                'message' => $post->content,
            ];
            
            if ($post->media_files && !empty($post->media_files)) {
                $payload['url'] = $post->media_files[0];
            }
            
            $response = $this->makeApiRequest('post', 
                "https://graph.facebook.com/v" . $this->getApiVersion('facebook', '18.0') . "/{$account->account_id}/feed",
                $payload,
                ['Authorization' => 'Bearer ' . $this->getAccessToken($account)]
            );

            if ($response->successful()) {
                return $this->handleApiSuccess($post, $response);
            } else {
                return $this->handleApiError($post, 'Facebook', $response);
            }
        } catch (\Exception $e) {
            return $this->handleException($post, 'Facebook', $e);
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
