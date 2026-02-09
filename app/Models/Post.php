<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'social_account_id', // Keep for backward compatibility but nullable
        'title',
        'content',
        'media_files',
        'status',
        'scheduled_at',
        'published_at',
        'platform_specific_data',
        'likes_count',
        'comments_count',
        'shares_count',
        'facebook_settings',
        'instagram_settings',
        'x_settings',
        'linkedin_settings',
        'youtube_settings',
        'tiktok_settings',
        'other_platform_settings',
    ];

    protected $casts = [
        'media_files' => 'array',
        'ai_generated_images' => 'array',
        'platform_specific_data' => 'array',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'facebook_settings' => 'array',
        'instagram_settings' => 'array',
        'x_settings' => 'array',
        'linkedin_settings' => 'array',
        'youtube_settings' => 'array',
        'tiktok_settings' => 'array',
        'other_platform_settings' => 'array',
    ];

    // Relationship with user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Many-to-many relationship with social accounts
    public function socialAccounts()
    {
        return $this->belongsToMany(SocialAccount::class, 'post_social_accounts')
            ->withTimestamps();
    }

    // Legacy single social account relationship (for backward compatibility)
    public function socialAccount()
    {
        return $this->belongsTo(SocialAccount::class);
    }

    // Add an AI-generated image to the post
    public function addAiGeneratedImage(array $imageData)
    {
        $aiImages = $this->ai_generated_images ?? [];
        $aiImages[] = $imageData;
        $this->update(['ai_generated_images' => $aiImages]);
        
        // Also add to media_files if not already present
        $mediaFiles = $this->media_files ?? [];
        if (!in_array($imageData['url'], $mediaFiles)) {
            $mediaFiles[] = $imageData['url'];
            $this->update(['media_files' => $mediaFiles]);
        }
        
        return $this;
    }

    // Relationship with leads
    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    // Relationship with engagements
    public function engagements()
    {
        return $this->hasMany(Engagement::class);
    }
}