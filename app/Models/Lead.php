<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'social_account_id',
        'post_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'platform',
        'platform_lead_id',
        'source_type',
        'message',
        'platform_data',
        'status',
        'contacted_at',
        'converted_at',
        'custom_fields',
    ];

    protected $casts = [
        'platform_data' => 'array',
        'custom_fields' => 'array',
        'contacted_at' => 'datetime',
        'converted_at' => 'datetime',
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
}