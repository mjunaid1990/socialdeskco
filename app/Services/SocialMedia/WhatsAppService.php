<?php

namespace App\Services\SocialMedia;

use App\Models\Post;
use App\Models\SocialAccount;

class WhatsAppService extends BaseSocialMediaService
{
    public function publishPost(Post $post, SocialAccount $account)
    {
        try {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $account->account_id,
                'type' => 'text',
                'text' => [
                    'body' => $post->content,
                ],
            ];
            
            if ($post->media_files && !empty($post->media_files)) {
                unset($payload['text']);
                $payload['type'] = 'image';
                $payload['image'] = [
                    'link' => $post->media_files[0],
                    'caption' => $post->content,
                ];
            }
            
            $response = $this->makeApiRequest('post',
                "https://graph.facebook.com/v" . $this->getApiVersion('whatsapp', '18.0') . "/{$account->phone_number_id}/messages",
                $payload,
                ['Authorization' => 'Bearer ' . $this->getAccessToken($account)]
            );

            if ($response->successful()) {
                return $this->handleApiSuccess($post, $response);
            } else {
                return $this->handleApiError($post, 'WhatsApp', $response);
            }
        } catch (\Exception $e) {
            return $this->handleException($post, 'WhatsApp', $e);
        }
    }

    public function getPlatformName(): string
    {
        return 'whatsapp';
    }
}
