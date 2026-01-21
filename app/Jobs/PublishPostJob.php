<?php

namespace App\Jobs;

use App\Models\Post;
use App\Services\SocialMediaService;
use App\Services\Monitoring\PerformanceMonitorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 10;

    protected $post;

    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    public function handle(SocialMediaService $socialMediaService)
    {
        $monitor = new PerformanceMonitorService();
        $jobClass = static::class;
        
        try {
            $result = $monitor->measureCallback('job_publish_post', function() use ($socialMediaService) {
                // Update post status to processing
                $this->post->update(['status' => 'processing']);
                
                // Publish the post using the service
                return $socialMediaService->publishPost($this->post);
            });
            
            if ($result) {
                Log::info("Successfully published post {$this->post->id} to {$this->post->socialAccount->platform}");
                $monitor->recordJobProcessing($jobClass, $monitor->getMetrics()['job_publish_post']['duration'] ?? 0, true);
            } else {
                Log::warning("Failed to publish post {$this->post->id}");
                $duration = $monitor->getMetrics()['job_publish_post']['duration'] ?? 0;
                $monitor->recordJobProcessing($jobClass, $duration, false);
                $this->fail();
            }
        } catch (\Exception $e) {
            $duration = $monitor->getMetrics()['job_publish_post']['duration'] ?? 0;
            $monitor->recordJobProcessing($jobClass, $duration, false);
            
            Log::error("Error publishing post {$this->post->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->fail($e);
        }
    }

    public function failed(\Throwable $exception = null)
    {
        $this->post->update([
            'status' => 'failed',
            'platform_specific_data' => array_merge(
                $this->post->platform_specific_data ?: [],
                ['error' => $exception?->getMessage()]
            )
        ]);
        
        Log::error("PublishPostJob failed for post {$this->post->id}", [
            'error' => $exception?->getMessage(),
            'trace' => $exception?->getTraceAsString()
        ]);
    }
}