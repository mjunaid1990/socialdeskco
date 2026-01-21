<?php

namespace App\Services\SocialMedia;

use App\Models\Post;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Helpers\SettingsHelper;

abstract class BaseSocialMediaService implements SocialMediaPlatformInterface
{
    protected function updatePostStatus(Post $post, string $status, array $data = []): void
    {
        $post->update([
            'status' => $status,
            'platform_specific_data' => $data,
            'published_at' => $status === 'published' ? now() : $post->published_at,
        ]);
    }

    protected function logApiError(string $platform, array $context): void
    {
        Log::error("{$platform} API Error", $context);
    }

    protected function makeApiRequest(string $method, string $url, array $payload = [], array $headers = [])
    {
        $defaultHeaders = [
            'Content-Type' => 'application/json',
        ];

        return Http::withHeaders(array_merge($defaultHeaders, $headers))
            ->{$method}($url, $payload);
    }

    protected function handleApiError(Post $post, string $platform, $response, array $additionalContext = []): bool
    {
        $errorData = $response->json() ?? [];
        $context = array_merge([
            'response' => $errorData,
            'status_code' => $response->status(),
            'post_id' => $post->id
        ], $additionalContext);

        $this->logApiError($platform, $context);
        $this->updatePostStatus($post, 'failed', $errorData);

        return false;
    }

    protected function handleApiSuccess(Post $post, $response): array
    {
        $responseData = $response->json();
        $this->updatePostStatus($post, 'published', $responseData);
        return $responseData;
    }

    protected function handleException(Post $post, string $platform, \Exception $e): bool
    {
        $this->logApiError($platform, [
            'error' => $e->getMessage(),
            'post_id' => $post->id
        ]);
        
        $this->updatePostStatus($post, 'failed', ['error' => $e->getMessage()]);
        return false;
    }

    protected function getApiVersion(string $platform, string $defaultVersion): string
    {
        return SettingsHelper::get("{$platform}_api_version", $defaultVersion);
    }

    protected function getClientId(string $platform): string
    {
        return SettingsHelper::get("{$platform}_client_id");
    }

    protected function getClientSecret(string $platform): string
    {
        return SettingsHelper::get("{$platform}_client_secret");
    }

    protected function getAccessToken(SocialAccount $account): string
    {
        return $account->access_token;
    }

    protected function getRefreshToken(SocialAccount $account): ?string
    {
        return $account->refresh_token;
    }

    protected function updateAccountTokens(SocialAccount $account, array $tokenData): void
    {
        $updateData = [
            'access_token' => $tokenData['access_token'],
        ];

        if (isset($tokenData['expires_in'])) {
            $updateData['token_expires_at'] = now()->addSeconds($tokenData['expires_in']);
        }

        if (isset($tokenData['refresh_token'])) {
            $updateData['refresh_token'] = $tokenData['refresh_token'];
        }

        $account->update($updateData);
    }

    public function refreshToken(SocialAccount $account): bool
    {
        if (!$this->getRefreshToken($account)) {
            return false;
        }

        return false; // Default implementation - override in specific platforms
    }

    abstract public function getPlatformName(): string;
}
