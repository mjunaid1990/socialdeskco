<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Engagement extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'social_account_id',
        'post_id',
        'engagement_type',
        'platform',
        'platform_engagement_id',
        'actor_username',
        'actor_full_name',
        'actor_platform_id',
        'content',
        'engagement_score',
        'platform_data',
        'is_automated_response',
        'engaged_at',
        'is_spam',
        'spam_reason',
        'is_duplicate',
        'duplicate_of',
        'content_hash',
        'processed_at',
    ];

    protected $casts = [
        'platform_data' => 'array',
        'engaged_at' => 'datetime',
        'is_automated_response' => 'boolean',
        'is_spam' => 'boolean',
        'is_duplicate' => 'boolean',
        'processed_at' => 'datetime',
    ];

    // Relationship with user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship with social account
    public function socialAccount()
    {
        return $this->belongsTo(SocialAccount::class);
    }

    // Relationship with post
    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    // Relationship with duplicate of
    public function duplicateOf()
    {
        return $this->belongsTo(Engagement::class, 'duplicate_of');
    }

    // Relationship with duplicates
    public function duplicates()
    {
        return $this->hasMany(Engagement::class, 'duplicate_of');
    }

    // Relationship with spam detection logs
    public function spamDetectionLogs()
    {
        return $this->hasMany(SpamDetectionLog::class);
    }

    // Scope for non-spam engagements
    public function scopeNotSpam($query)
    {
        return $query->where('is_spam', false);
    }

    // Scope for non-duplicate engagements
    public function scopeNotDuplicate($query)
    {
        return $query->where('is_duplicate', false);
    }

    // Scope for unprocessed engagements
    public function scopeUnprocessed($query)
    {
        return $query->whereNull('processed_at');
    }

    // Generate content hash for duplicate detection
    public function generateContentHash()
    {
        if (!empty($this->content)) {
            $this->content_hash = md5(strtolower(trim($this->content)));
            $this->save();
        }
    }

    // Mark as processed
    public function markAsProcessed()
    {
        $this->update(['processed_at' => now()]);
    }

    // Check if engagement can be processed
    public function canBeProcessed()
    {
        return !$this->is_spam && !$this->is_duplicate && !$this->processed_at;
    }
}