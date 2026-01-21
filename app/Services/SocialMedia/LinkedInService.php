<?php

namespace App\Services\SocialMedia;

use App\Models\Post;
use App\Models\SocialAccount;

class LinkedInService extends BaseSocialMediaService
{
    public function publishPost(Post $post, SocialAccount $account)
    {
        try {
            $payload = [
                'author' => "urn:li:person:{$account->account_id}",
                'lifecycleState' => 'PUBLISHED',
                'specificContent' => [
                    'com.linkedin.ugc.ShareContent' => [
                        'shareCommentary' => [
                            'text' => $post->content,
                        ],
                        'shareMediaCategory' => 'NONE',
                    ],
                ],
                'visibility' => [
                    'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
                ],
            ];
            
            if ($post->media_files && !empty($post->media_files)) {
                $payload['specificContent']['com.linkedin.ugc.ShareContent']['shareMediaCategory'] = 'IMAGE';
                $payload['specificContent']['com.linkedin.ugc.ShareContent']['media'] = [
                    [
                        'status' => 'READY',
                        'description' => 'Description for the image',
                        'location' => $post->media_files[0],
                        'title' => [
                            'text' => 'Image title',
                        ],
                    ],
                ];
            }
            
            $response = $this->makeApiRequest('post',
                'https://api.linkedin.com/v' . $this->getApiVersion('linkedin', '2') . '/ugcPosts',
                $payload,
                [
                    'Authorization' => 'Bearer ' . $this->getAccessToken($account),
                    'X-Restli-Protocol-Version' => '2.0.0',
                ]
            );

            if ($response->successful()) {
                return $this->handleApiSuccess($post, $response);
            } else {
                return $this->handleApiError($post, 'LinkedIn', $response);
            }
        } catch (\Exception $e) {
            return $this->handleException($post, 'LinkedIn', $e);
        }
    }

    public function refreshToken(SocialAccount $account): bool
    {
        if (!$this->getRefreshToken($account)) {
            return false;
        }

        $response = Http::asForm()->post('https://www.linkedin.com/oauth/v2/accessToken', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->getRefreshToken($account),
            'client_id' => $this->getClientId('linkedin'),
            'client_secret' => $this->getClientSecret('linkedin'),
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $this->updateAccountTokens($account, $data);
            return true;
        } else {
            $this->logApiError('LinkedIn token refresh failed', [
                'status_code' => $response->status(),
                'response' => $response->json(),
                'account_id' => $account->id,
            ]);
            return false;
        }
    }

    public function getPlatformName(): string
    {
        return 'linkedin';
    }
}
