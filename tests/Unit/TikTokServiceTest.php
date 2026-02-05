<?php

namespace Tests\Unit;

use App\Models\Post;
use App\Models\SocialAccount;
use App\Services\SocialMedia\TikTokService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TikTokServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_platform_name_returns_tiktok()
    {
        $tikTokService = new TikTokService();
        $this->assertEquals('tiktok', $tikTokService->getPlatformName());
    }

    public function test_delete_post_not_supported()
    {
        $tikTokService = new TikTokService();
        $post = Post::factory()->create();
        $account = SocialAccount::factory()->create(['platform' => 'tiktok']);
        
        $result = $tikTokService->deletePost($post, $account);
        $this->assertFalse($result);
    }

    public function test_service_extends_base_class()
    {
        $tikTokService = new TikTokService();
        $this->assertInstanceOf(\App\Services\SocialMedia\BaseSocialMediaService::class, $tikTokService);
    }
}