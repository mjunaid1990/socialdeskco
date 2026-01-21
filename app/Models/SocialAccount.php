<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'platform',
        'account_name',
        'account_id',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'profile_data',
        'is_active',
        'last_sync_at',
    ];

    protected $casts = [
        'profile_data' => 'array',
        'token_expires_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Relationship with user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship with posts
    public function posts()
    {
        return $this->hasMany(Post::class);
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

    // Relationship with automation rules
    public function automationRules()
    {
        return $this->hasMany(AutomationRule::class);
    }
}