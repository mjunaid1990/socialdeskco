<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutomationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'social_account_id',
        'name',
        'trigger_type',
        'trigger_conditions',
        'action_type',
        'action_parameters',
        'is_active',
        'execution_count',
        'last_executed_at',
    ];

    protected $casts = [
        'trigger_conditions' => 'array',
        'action_parameters' => 'array',
        'is_active' => 'boolean',
        'last_executed_at' => 'datetime',
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
}