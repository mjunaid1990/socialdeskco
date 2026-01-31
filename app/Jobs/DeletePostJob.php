<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\SocialAccount;
use App\Services\SocialMediaService;
use App\Services\Monitoring\PerformanceMonitorService;
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
        $monitor = new PerformanceMonitorService();
        $jobClass = static::class;
        
        try {
            Log::info("DeletePostJob: Starting delete for post {$this->post->id} from {$this->account->platform}");
            
            $result = $monitor->measureCallback('job_delete_post', function() use ($socialMediaService) {
                return $socialMediaService->deletePost($this->post, $this->account);
            });
            
            if ($result) {
                Log::info("DeletePostJob: Successfully deleted post {$this->post->id} from {$this->account->platform}");
                $monitor->recordJobProcessing($jobClass, $monitor->getMetrics()['job_delete_post']['duration'] ?? 0, true);
            } else {
                Log::warning("DeletePostJob: Failed to delete post {$this->post->id} from {$this->account->platform}");
                $duration = $monitor->getMetrics()['job_delete_post']['duration'] ?? 0;
                $monitor->recordJobProcessing($jobClass, $duration, false);
                $this->fail();
            }
        } catch (\Exception $e) {
            $duration = $monitor->getMetrics()['job_delete_post']['duration'] ?? 0;
            $monitor->recordJobProcessing($jobClass, $duration, false);
            
            Log::error("DeletePostJob: Error deleting post {$this->post->id}", [
                'error' => $e->getMessage(),
                'platform' => $this->account->platform,
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->fail($e);
        }
    }

    public function failed(\Throwable $exception = null)
    {
        Log::error("DeletePostJob failed for post {$this->post->id} from {$this->account->platform}", [
            'error' => $exception?->getMessage(),
            'trace' => $exception?->getTraceAsString()
        ]);
    }
}
