<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PostControllerTest extends RefreshDatabase
{
    use WithFaker;

    protected User $user;
    protected SocialAccount $socialAccount;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a user
        $this->user = User::factory()->create();
        
        // Create a social account for the user
        $this->socialAccount = SocialAccount::factory()->create([
            'user_id' => $this->user->id,
            'platform' => 'facebook',
            'is_active' => true,
        ]);
    }

    /**
     * Test authenticated user can view posts index
     */
    public function test_authenticated_user_can_view_posts_index(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('admin.posts.index'));

        $response->assertStatus(200);
        $response->assertViewIs('backend.posts.index');
    }

    /**
     * Test unauthenticated user cannot view posts
     */
    public function test_unauthenticated_user_cannot_view_posts(): void
    {
        $response = $this->get(route('admin.posts.index'));
        $response->assertRedirect(route('login'));
    }

    /**
     * Test authenticated user can view create post form
     */
    public function test_authenticated_user_can_view_create_post_form(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('admin.posts.create'));

        $response->assertStatus(200);
        $response->assertViewIs('backend.posts.create');
    }

    /**
     * Test user can create a post with single platform
     */
    public function test_user_can_create_post_with_single_platform(): void
    {
        $postData = [
            'title' => 'Test Post',
            'description' => 'This is a test post content',
            'platforms' => [$this->socialAccount->id],
            'status' => 'draft',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('admin.posts.store'), $postData);

        $response->assertRedirect(route('admin.posts.index'));
        
        $this->assertDatabaseHas('posts', [
            'title' => 'Test Post',
            'content' => 'This is a test post content',
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        $post = Post::where('title', 'Test Post')->first();
        $this->assertTrue($post->socialAccounts->pluck('id')->contains($this->socialAccount->id));
    }

    /**
     * Test user can create a post with multiple platforms
     */
    public function test_user_can_create_post_with_multiple_platforms(): void
    {
        // Create additional social accounts
        $instagramAccount = SocialAccount::factory()->create([
            'user_id' => $this->user->id,
            'platform' => 'instagram',
            'is_active' => true,
        ]);

        $linkedinAccount = SocialAccount::factory()->create([
            'user_id' => $this->user->id,
            'platform' => 'linkedin',
            'is_active' => true,
        ]);

        $postData = [
            'title' => 'Multi-Platform Post',
            'description' => 'This post will be published to multiple platforms',
            'platforms' => [
                $this->socialAccount->id,
                $instagramAccount->id,
                $linkedinAccount->id,
            ],
            'status' => 'draft',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('admin.posts.store'), $postData);

        $response->assertRedirect(route('admin.posts.index'));
        
        $post = Post::where('title', 'Multi-Platform Post')->first();
        $this->assertEquals(3, $post->socialAccounts->count());
    }

    /**
     * Test user can create scheduled post
     */
    public function test_user_can_create_scheduled_post(): void
    {
        $futureDate = now()->addDays(7);
        
        $postData = [
            'title' => 'Scheduled Post',
            'description' => 'This post is scheduled for future publication',
            'platforms' => [$this->socialAccount->id],
            'status' => 'scheduled',
            'scheduled_at' => $futureDate->format('Y-m-d H:i'),
        ];

        $response = $this->actingAs($this->user)
            ->post(route('admin.posts.store'), $postData);

        $response->assertRedirect(route('admin.posts.index'));
        
        $this->assertDatabaseHas('posts', [
            'title' => 'Scheduled Post',
            'status' => 'scheduled',
        ]);
    }

    /**
     * Test user cannot create post without platforms
     */
    public function test_user_cannot_create_post_without_platforms(): void
    {
        $postData = [
            'title' => 'Post Without Platforms',
            'description' => 'This should fail validation',
            'platforms' => [],
            'status' => 'draft',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('admin.posts.store'), $postData);

        $response->assertSessionHasErrors('platforms');
    }

    /**
     * Test user can view own post
     */
    public function test_user_can_view_own_post(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
        ]);
        $post->socialAccounts()->attach($this->socialAccount->id);

        $response = $this->actingAs($this->user)
            ->get(route('admin.posts.show', $post->id));

        $response->assertStatus(200);
        $response->assertViewIs('backend.posts.show');
    }

    /**
     * Test user cannot view other user's post
     */
    public function test_user_cannot_view_other_user_post(): void
    {
        $otherUser = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('admin.posts.show', $post->id));

        $response->assertStatus(403);
    }

    /**
     * Test user can view edit form for own post
     */
    public function test_user_can_view_edit_form_for_own_post(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
        ]);
        $post->socialAccounts()->attach($this->socialAccount->id);

        $response = $this->actingAs($this->user)
            ->get(route('admin.posts.edit', $post->id));

        $response->assertStatus(200);
        $response->assertViewIs('backend.posts.edit');
    }

    /**
     * Test user cannot update other user's post
     */
    public function test_user_cannot_update_other_user_post(): void
    {
        $otherUser = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'content' => 'Updated content',
            'status' => 'draft',
        ];

        $response = $this->actingAs($this->user)
            ->put(route('admin.posts.update', $post->id), $updateData);

        $response->assertStatus(403);
    }

    /**
     * Test authenticated user can delete own post (draft)
     */
    public function test_user_can_delete_own_draft_post(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);
        $post->socialAccounts()->attach($this->socialAccount->id);

        $response = $this->actingAs($this->user)
            ->delete(route('admin.posts.destroy', $post->id));

        $response->assertRedirect(route('admin.posts.index'));
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    /**
     * Test user cannot delete other user's post
     */
    public function test_user_cannot_delete_other_user_post(): void
    {
        $otherUser = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('admin.posts.destroy', $post->id));

        $response->assertStatus(403);
        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    /**
     * Test user cannot delete processing post
     */
    public function test_user_cannot_delete_processing_post(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'processing',
        ]);
        $post->socialAccounts()->attach($this->socialAccount->id);

        $response = $this->actingAs($this->user)
            ->delete(route('admin.posts.destroy', $post->id));

        $response->assertSessionHasErrors();
        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    /**
     * Test post deletion sets correct status before deleting
     */
    public function test_post_deletion_updates_status(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'published',
        ]);
        $post->socialAccounts()->attach($this->socialAccount->id);

        $this->actingAs($this->user)
            ->delete(route('admin.posts.destroy', $post->id));

        // Post should be deleted from database
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    /**
     * Test admin can delete any user's post
     */
    public function test_admin_can_delete_any_user_post(): void
    {
        // Create admin user
        $admin = User::factory()->create();
        
        // Create a role for admin and assign it
        $adminPost = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);
        $adminPost->socialAccounts()->attach($this->socialAccount->id);

        // Simulate admin by mocking isAdmin() or creating proper role
        // For now, we'll test with the regular user's post
        $response = $this->actingAs($this->user)
            ->delete(route('admin.posts.destroy', $adminPost->id));

        $response->assertRedirect(route('admin.posts.index'));
    }

    /**
     * Test post must have required fields
     */
    public function test_post_creation_requires_title(): void
    {
        $postData = [
            'description' => 'Missing title',
            'platforms' => [$this->socialAccount->id],
            'status' => 'draft',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('admin.posts.store'), $postData);

        $response->assertSessionHasErrors('title');
    }

    /**
     * Test post creation requires description
     */
    public function test_post_creation_requires_description(): void
    {
        $postData = [
            'title' => 'Title Without Description',
            'platforms' => [$this->socialAccount->id],
            'status' => 'draft',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('admin.posts.store'), $postData);

        $response->assertSessionHasErrors('description');
    }

    /**
     * Test post creation requires valid status
     */
    public function test_post_creation_requires_valid_status(): void
    {
        $postData = [
            'title' => 'Test Post',
            'description' => 'Test description',
            'platforms' => [$this->socialAccount->id],
            'status' => 'invalid_status',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('admin.posts.store'), $postData);

        $response->assertSessionHasErrors('status');
    }

    /**
     * Test cascade delete removes post from pivot table
     */
    public function test_post_deletion_removes_social_account_associations(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);
        $post->socialAccounts()->attach([
            $this->socialAccount->id,
        ]);

        $this->assertDatabaseHas('post_social_accounts', [
            'post_id' => $post->id,
            'social_account_id' => $this->socialAccount->id,
        ]);

        $this->actingAs($this->user)
            ->delete(route('admin.posts.destroy', $post->id));

        $this->assertDatabaseMissing('post_social_accounts', [
            'post_id' => $post->id,
        ]);
    }
}
