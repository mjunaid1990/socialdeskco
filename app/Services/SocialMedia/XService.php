<?php

namespace App\Services\SocialMedia;

use App\Models\Post;
use App\Models\SocialAccount;

class XService extends BaseSocialMediaService
{
    public function publishPost(Post $post, SocialAccount $account)
    {
        try {
            $payload = [
                'text' => $post->content,
            ];
            
            if ($post->media_files && !empty($post->media_files)) {
                // X API doesn't directly support media upload in the same request
                // This would require a separate media upload implementation
            }
            
            $response = $this->makeApiRequest('post',
                "https://api.x.com/2/tweets",
                $payload,
                ['Authorization' => 'Bearer ' . $this->getAccessToken($account)]
            );

            if ($response->successful()) {
                return $this->handleApiSuccess($post, $response);
            } else {
                return $this->handleApiError($post, 'X', $response);
            }
        } catch (\Exception $e) {
            return $this->handleException($post, 'X', $e);
        }
    }

    public function refreshToken(SocialAccount $account): bool
    {
        if (!$this->getRefreshToken($account)) {
            return false;
        }

        $response = Http::asForm()->withBasicAuth(
            $this->getClientId('x'),
            $this->getClientSecret('x')
        )->post('https://api.x.com/2/oauth2/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->getRefreshToken($account),
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $this->updateAccountTokens($account, $data);
            return true;
        }
        
        $this->logApiError('X token refresh failed', [
            'status_code' => $response->status(),
            'response' => $response->json(),
            'account_id' => $account->id,
        ]);
        
        return false;
    }

    public function getPlatformName(): string
    {
        return 'x';
    }
}
