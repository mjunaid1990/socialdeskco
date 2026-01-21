<?php

namespace App\Console\Commands;

use App\Jobs\PublishPostJob;
use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessScheduledPosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'social-media:process-scheduled-posts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process posts that are scheduled to be published';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing scheduled posts...');

        // Find posts that are scheduled and ready to be published
        $scheduledPosts = Post::where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->whereNull('published_at')
            ->get();

        $processedCount = 0;

        foreach ($scheduledPosts as $post) {
            try {
                // Dispatch job to publish the post
                PublishPostJob::dispatch($post);
                
                $this->info("Dispatched job to publish post {$post->id}");
                $processedCount++;
                
                // Update the post status to processing
                $post->update(['status' => 'processing']);
            } catch (\Exception $e) {
                $this->error("Error processing post {$post->id}: " . $e->getMessage());
                Log::error("Error dispatching job for scheduled post {$post->id}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        $this->info("Processed {$processedCount} scheduled posts.");
        
        return 0;
    }
}