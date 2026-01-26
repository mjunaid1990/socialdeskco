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

    protected function isTokenExpiredError(array $errorData): bool
    {
        // Check for common token expiration error codes and messages
        $tokenExpiredPatterns = [
            'error.code' => [190, 190], // Facebook/Instagram OAuth error code
            'error.message' => [
                'Error validating access token',
                'Session has expired',
                'Invalid OAuth access token',
                'The access token expired',
                'token has expired',
                'Invalid OAuth access token - Cannot parse access token',
            ]
        ];

        // Check error code
        if (isset($errorData['error']['code']) && in_array($errorData['error']['code'], [190, 401])) {
            return true;
        }

        // Check error message
        if (isset($errorData['error']['message'])) {
            $message = $errorData['error']['message'];
            foreach ($tokenExpiredPatterns['error.message'] as $pattern) {
                if (stripos($message, $pattern) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function getPlatformService(string $platform): ?SocialMediaPlatformInterface
    {
        $factory = new \App\Services\SocialMedia\SocialMediaServiceFactory();
        return $factory->create($platform);
    }

    protected function handleApiError(Post $post, string $platform, $response, array $additionalContext = [], ?SocialAccount $account = null): bool
    {
        $errorData = $response->json() ?? [];
        $context = array_merge([
            'response' => $errorData,
            'status_code' => $response->status(),
            'post_id' => $post->id
        ], $additionalContext);

        // Check for token expiration errors
        if ($this->isTokenExpiredError($errorData) && $account) {
            Log::warning('Token expired, attempting refresh...', [
                'platform' => $platform,
                'post_id' => $post->id,
                'account_id' => $account->id,
            ]);

            // Get the appropriate service for token refresh
            $service = $this->getPlatformService($platform);
            if ($service && $service->refreshToken($account)) {
                Log::info('Token refreshed successfully, retrying post...', [
                    'platform' => $platform,
                    'post_id' => $post->id,
                    'account_id' => $account->id,
                ]);
                
                // Retry the post once after successful token refresh
                return $service->publishPost($post, $account);
            } else {
                Log::error('Token refresh failed', [
                    'platform' => $platform,
                    'post_id' => $post->id,
                    'account_id' => $account->id,
                ]);
            }
        }

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

    public function deletePost(Post $post, SocialAccount $account)
    {
        // Default implementation - override in specific platforms
        Log::warning("Delete post not implemented for platform", [
            'platform' => $this->getPlatformName(),
            'post_id' => $post->id,
            'account_id' => $account->id
        ]);
        return false;
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
