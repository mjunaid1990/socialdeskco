<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\SocialAccount;
use App\Services\SocialMediaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeletePostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 10;

    private $postId;
    private $accountId;

    public function __construct(int $postId, int $accountId)
    {
        $this->postId = $postId;
        $this->accountId = $accountId;
    }

    public function handle(SocialMediaService $socialMediaService)
    {
        try {
            // Retrieve fresh models
            $post = Post::find($this->postId);
            $account = SocialAccount::find($this->accountId);

            if (!$post) {
                Log::error("DeletePostJob: Post not found", ['post_id' => $this->postId]);
                return;
            }

            if (!$account) {
                Log::error("DeletePostJob: Social account not found", ['account_id' => $this->accountId]);
                return;
            }

            Log::info("DeletePostJob: Starting deletion for post {$post->id} on platform {$account->platform}");
            Log::info("DeletePostJob: Post platform_specific_data", ['data' => $post->platform_specific_data]);

            $result = $socialMediaService->deletePost($post, $account);
            Log::info("DeletePostJob: SocialMediaService deletePost returned", ['result' => $result]);

            if ($result) {
                Log::info("DeletePostJob: Successfully deleted post {$post->id} from {$account->platform}");
            } else {
                Log::warning("DeletePostJob: Failed to delete post {$post->id} from {$account->platform}");
            }
        } catch (\Exception $e) {
            Log::error("DeletePostJob: Exception deleting post {$this->postId}", [
                'platform' => $this->accountId,
                'account_id' => $this->accountId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->fail($e);
        }
    }

    public function failed(\Throwable $exception = null)
    {
        Log::error("DeletePostJob failed for post {$this->post->id} on {$this->account->platform}", [
            'error' => $exception?->getMessage(),
        ]);
    }
}