<?php

namespace App\Services\SocialMedia;

use App\Models\Post;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YouTubeService extends BaseSocialMediaService
{
    public function publishPost(Post $post, SocialAccount $account)
    {
        try {
            $hasMedia = $post->media_files && !empty($post->media_files);

            if (!$hasMedia) {
                Log::warning('YouTube requires video content, but no media files found', [
                    'post_id'    => $post->id,
                    'account_id' => $account->id,
                ]);
                $this->updatePostStatus($post, 'failed', [
                    'error' => 'YouTube requires video content. Please upload a video file.',
                ]);
                return false;
            }

            $videoUrl = $post->media_files[0];

            if (!$this->isVideoUrl($videoUrl)) {
                Log::warning('YouTube requires video files, but non-video file provided', [
                    'post_id'    => $post->id,
                    'video_url'  => $videoUrl,
                    'account_id' => $account->id,
                ]);
                $this->updatePostStatus($post, 'failed', [
                    'error' => 'YouTube only supports video files. Please upload a valid video file.',
                ]);
                return false;
            }

            return $this->publishVideo($post, $account, $videoUrl);
        } catch (\Exception $e) {
            return $this->handleException($post, 'YouTube', $e);
        }
    }

    private function publishVideo(Post $post, SocialAccount $account, string $videoUrl)
    {
        $accessToken = $this->getAccessToken($account);

        // Step 1: Initiate a resumable upload session
        $videoMetadata = [
            'snippet' => [
                'title'       => $post->title ?? 'Untitled',
                'description' => $post->content,
                'categoryId'  => $post->youtube_settings['category'] ?? '22',
                'tags'        => !empty($post->youtube_settings['tags'])
                    ? explode(',', $post->youtube_settings['tags'])
                    : [],
            ],
            'status' => [
                'privacyStatus'            => $post->youtube_settings['privacy'] ?? 'public',
                'selfDeclaredMadeForKids'  => false,
            ],
        ];

        $initiateResponse = Http::withHeaders([
            'Authorization'          => 'Bearer ' . $accessToken,
            'Content-Type'           => 'application/json',
            'X-Upload-Content-Type'  => $this->getMimeType($videoUrl),
        ])->post(
            'https://www.googleapis.com/upload/youtube/v3/videos?uploadType=resumable&part=snippet,status',
            $videoMetadata
        );

        if (!$initiateResponse->successful()) {
            Log::error('YouTube resumable upload initiation failed', [
                'post_id'     => $post->id,
                'status_code' => $initiateResponse->status(),
                'response'    => $initiateResponse->json(),
            ]);
            return $this->handleApiError($post, 'YouTube', $initiateResponse);
        }

        // Step 2: Get the upload URL from Location header
        $uploadUrl = $initiateResponse->header('Location');

        if (!$uploadUrl) {
            Log::error('YouTube did not return an upload URL (Location header missing)', [
                'post_id' => $post->id,
            ]);
            $this->updatePostStatus($post, 'failed', ['error' => 'No upload URL returned by YouTube.']);
            return false;
        }

        // Step 3: Upload the actual video content
        // Read video content from storage path
        $videoContent = @file_get_contents($videoUrl);
        if ($videoContent === false) {
            Log::error('YouTube: Failed to read video file', [
                'post_id'   => $post->id,
                'video_url' => $videoUrl,
            ]);
            $this->updatePostStatus($post, 'failed', ['error' => 'Could not read video file for upload.']);
            return false;
        }

        $uploadResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type'  => $this->getMimeType($videoUrl),
        ])->withBody($videoContent, $this->getMimeType($videoUrl))->put($uploadUrl);

        if ($uploadResponse->successful()) {
            $responseData   = $uploadResponse->json();
            $youtubeVideoId = $responseData['id'] ?? null;

            Log::info('YouTube video upload completed', [
                'post_id'          => $post->id,
                'youtube_video_id' => $youtubeVideoId,
            ]);

            $this->updatePostStatus($post, 'published', [
                'youtube_video_id' => $youtubeVideoId,
                'results'          => [
                    $account->id => [
                        'id'           => $youtubeVideoId,
                        'platform'     => 'youtube',
                        'published_at' => now()->toISOString(),
                    ],
                ],
                'published_at'  => now()->toISOString(),
                'success_count' => 1,
                'failure_count' => 0,
            ]);

            return $youtubeVideoId;
        } else {
            Log::error('YouTube video upload failed', [
                'post_id'     => $post->id,
                'status_code' => $uploadResponse->status(),
                'response'    => $uploadResponse->body(),
            ]);
            return $this->handleApiError($post, 'YouTube', $uploadResponse);
        }
    }

    private function getMimeType(string $url): string
    {
        $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
        return match($extension) {
            'mp4'  => 'video/mp4',
            'mov'  => 'video/quicktime',
            'avi'  => 'video/x-msvideo',
            'wmv'  => 'video/x-ms-wmv',
            'flv'  => 'video/x-flv',
            'webm' => 'video/webm',
            'mkv'  => 'video/x-matroska',
            '3gp'  => 'video/3gpp',
            'm4v'  => 'video/x-m4v',
            default => 'video/mp4',
        };
    }

    private function isVideoUrl(string $url): bool
    {
        $videoExtensions = ['mp4', 'mov', 'avi', 'wmv', 'flv', 'webm', 'mkv', '3gp', 'm4v'];
        $extension       = strtolower(pathinfo($url, PATHINFO_EXTENSION));
        return in_array($extension, $videoExtensions);
    }

    public function deletePost(Post $post, SocialAccount $account)
    {
        try {
            $platformData   = $post->platform_specific_data ?? [];
            $youtubeVideoId = $platformData['youtube_video_id']
                ?? $platformData['results'][$account->id]['id']
                ?? $platformData['id']
                ?? null;

            if (!$youtubeVideoId) {
                Log::warning('No YouTube video ID found for deletion', [
                    'post_id'    => $post->id,
                    'account_id' => $account->id,
                ]);
                return false;
            }

            $accessToken = $this->getAccessToken($account);

            Log::info('Attempting to delete YouTube video', [
                'post_id'          => $post->id,
                'youtube_video_id' => $youtubeVideoId,
            ]);

            // YouTube Data API v3 — DELETE /videos?id=VIDEO_ID
            $response = $this->makeApiRequest('delete',
                "https://www.googleapis.com/youtube/v3/videos?id={$youtubeVideoId}",
                [],
                ['Authorization' => 'Bearer ' . $accessToken]
            );

            // YouTube returns 204 No Content on successful deletion
            if ($response->successful() || $response->status() === 204) {
                Log::info('YouTube video deleted successfully', [
                    'post_id'          => $post->id,
                    'youtube_video_id' => $youtubeVideoId,
                ]);
                return true;
            } else {
                Log::error('Failed to delete YouTube video', [
                    'post_id'          => $post->id,
                    'youtube_video_id' => $youtubeVideoId,
                    'response'         => $response->json(),
                    'status_code'      => $response->status(),
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception when deleting YouTube video', [
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

        $response = $this->makeApiRequest('post', 'https://oauth2.googleapis.com/token', [
            'grant_type'    => 'refresh_token',
            'client_id'     => $this->getClientId('youtube'),
            'client_secret' => $this->getClientSecret('youtube'),
            'refresh_token' => $this->getRefreshToken($account),
        ]);

        if ($response->successful()) {
            $this->updateAccountTokens($account, $response->json());
            return true;
        }

        $this->logApiError('YouTube token refresh failed', [
            'status_code' => $response->status(),
            'response'    => $response->json(),
            'account_id'  => $account->id,
        ]);
        return false;
    }

    public function getPlatformName(): string
    {
        return 'youtube';
    }
}