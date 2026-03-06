<?php

namespace Tests\Feature;

use App\Jobs\DeletePostJob;
use App\Jobs\PublishPostJob;
use App\Models\Post;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\SocialMediaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PostQueueJobsTest extends RefreshDatabase
{
    protected User $user;
    protected SocialAccount $socialAccount;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        
        $this->socialAccount = SocialAccount::factory()->create([
            'user_id' => $this->user->id,
            'platform' => 'facebook',
            'is_active' => true,
            'access_token' => 'test_token',
        ]);
    }

    /**
     * Test PublishPostJob is dispatched when post is published
     */
    public function test_publish_post_job_is_dispatched_on_create(): void
    {
        Queue::fake();

        $postData = [
            'title' => 'Test Post',
            'description' => 'Test content',
            'platforms' => [$this->socialAccount->id],
            'status' => 'published',
        ];

        $this->actingAs($this->user)
            ->post(route('admin.posts.store'), $postData);

        Queue::assertPushed(PublishPostJob::class);
    }

    /**
     * Test PublishPostJob is not dispatched for draft posts
     */
    public function test_publish_post_job_not_dispatched_for_draft(): void
    {
        Queue::fake();

        $postData = [
            'title' => 'Draft Post',
            'description' => 'Draft content',
            'platforms' => [$this->socialAccount->id],
            'status' => 'draft',
        ];

        $this->actingAs($this->user)
            ->post(route('admin.posts.store'), $postData);

        Queue::assertNotPushed(PublishPostJob::class);
    }

    /**
     * Test DeletePostJob is dispatched when published post is deleted
     */
    public function test_delete_post_job_is_dispatched_for_published_post(): void
    {
        Queue::fake();

        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'published',
        ]);
        $post->socialAccounts()->attach($this->socialAccount->id);

        $this->actingAs($this->user)
            ->delete(route('admin.posts.destroy', $post->id));

        Queue::assertPushed(DeletePostJob::class);
    }

    /**
     * Test DeletePostJob is not dispatched for draft post deletion
     */
    public function test_delete_post_job_not_dispatched_for_draft_post(): void
    {
        Queue::fake();

        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);
        $post->socialAccounts()->attach($this->socialAccount->id);

        $this->actingAs($this->user)
            ->delete(route('admin.posts.destroy', $post->id));

        Queue::assertNotPushed(DeletePostJob::class);
    }

    /**
     * Test PublishPostJob handles post with multiple accounts
     */
    public function test_publish_post_job_handles_multiple_accounts(): void
    {
        Queue::fake();

        $instagramAccount = SocialAccount::factory()->create([
            'user_id' => $this->user->id,
            'platform' => 'instagram',
            'is_active' => true,
        ]);

        $postData = [
            'title' => 'Multi Account Post',
            'description' => 'Test content',
            'platforms' => [
                $this->socialAccount->id,
                $instagramAccount->id,
            ],
            'status' => 'published',
        ];

        $this->actingAs($this->user)
            ->post(route('admin.posts.store'), $postData);

        Queue::assertPushed(PublishPostJob::class, 1);
    }

    /**
     * Test DeletePostJob is dispatched for each platform when deleting published post
     */
    public function test_delete_post_job_dispatched_for_each_platform(): void
    {
        Queue::fake();

        $instagramAccount = SocialAccount::factory()->create([
            'user_id' => $this->user->id,
            'platform' => 'instagram',
            'is_active' => true,
        ]);

        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'published',
        ]);
        $post->socialAccounts()->attach([
            $this->socialAccount->id,
            $instagramAccount->id,
        ]);

        $this->actingAs($this->user)
            ->delete(route('admin.posts.destroy', $post->id));

        Queue::assertPushed(DeletePostJob::class, 2);
    }

    /**
     * Test scheduled post dispatches job if scheduled time is past
     */
    public function test_scheduled_post_dispatches_job_if_time_is_past(): void
    {
        Queue::fake();

        $pastTime = now()->subHour();

        $postData = [
            'title' => 'Past Scheduled Post',
            'description' => 'Already scheduled',
            'platforms' => [$this->socialAccount->id],
            'status' => 'scheduled',
            'scheduled_at' => $pastTime->format('Y-m-d H:i'),
        ];

        $this->actingAs($this->user)
            ->post(route('admin.posts.store'), $postData);

        Queue::assertPushed(PublishPostJob::class);
    }

    /**
     * Test job has correct retry and backoff configurations
     */
    public function test_publish_post_job_has_retry_configuration(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'published',
        ]);
        $post->socialAccounts()->attach($this->socialAccount->id);

        $job = new PublishPostJob($post);

        $this->assertEquals(3, $job->tries);
        $this->assertEquals(10, $job->backoff);
    }

    /**
     * Test delete job has correct retry and backoff configurations
     */
    public function test_delete_post_job_has_retry_configuration(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $job = new DeletePostJob($post, $this->socialAccount);

        $this->assertEquals(3, $job->tries);
        $this->assertEquals(10, $job->backoff);
    }
}
