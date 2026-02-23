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

    protected $post;
    protected $account;

    public function __construct(Post $post, SocialAccount $account)
    {
        $this->post = $post;
        $this->account = $account;
    }

    public function handle(SocialMediaService $socialMediaService)
    {
        try {
            Log::info("DeletePostJob: Starting deletion for post {$this->post->id} on platform {$this->account->platform}");
            Log::info("DeletePostJob: Post platform_specific_data", ['data' => $this->post->platform_specific_data]);

            $result = $socialMediaService->deletePost($this->post, $this->account);
            Log::info("DeletePostJob: SocialMediaService deletePost returned", ['result' => $result]);

            if ($result) {
                Log::info("DeletePostJob: Successfully deleted post {$this->post->id} from {$this->account->platform}");
            } else {
                Log::warning("DeletePostJob: Failed to delete post {$this->post->id} from {$this->account->platform}");
            }
        } catch (\Exception $e) {
            Log::error("DeletePostJob: Exception deleting post {$this->post->id}", [
                'platform' => $this->account->platform,
                'account_id' => $this->account->id,
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