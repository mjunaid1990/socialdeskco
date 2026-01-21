<?php

namespace App\Services\SocialMedia;

use App\Models\Post;
use App\Models\SocialAccount;

class TwitterService extends BaseSocialMediaService
{
    public function publishPost(Post $post, SocialAccount $account)
    {
        try {
            $response = $this->makeApiRequest('post',
                "https://api.twitter.com/v" . $this->getApiVersion('twitter', '2') . "/tweets",
                ['text' => $post->content],
                ['Authorization' => 'Bearer ' . $this->getAccessToken($account)]
            );

            if ($response->successful()) {
                return $this->handleApiSuccess($post, $response);
            } else {
                return $this->handleApiError($post, 'Twitter', $response);
            }
        } catch (\Exception $e) {
            return $this->handleException($post, 'Twitter', $e);
        }
    }

    public function refreshToken(SocialAccount $account): bool
    {
        if (!$this->getRefreshToken($account)) {
            return false;
        }

        $response = Http::asForm()->withBasicAuth(
            $this->getClientId('twitter'),
            $this->getClientSecret('twitter')
        )->post('https://api.twitter.com/oauth2/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->getRefreshToken($account),
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $this->updateAccountTokens($account, $data);
            return true;
        }
        
        return false;
    }

    public function getPlatformName(): string
    {
        return 'twitter';
    }
}
