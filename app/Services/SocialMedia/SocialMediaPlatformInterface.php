<?php

namespace App\Services\SocialMedia;

use App\Models\Post;
use App\Models\SocialAccount;

interface SocialMediaPlatformInterface
{
    public function publishPost(Post $post, SocialAccount $account);
    public function deletePost(Post $post, SocialAccount $account);
    public function refreshToken(SocialAccount $account): bool;
    public function getPlatformName(): string;
}
