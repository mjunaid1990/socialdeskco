<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'setting_key',
        'setting_value',
        'setting_type',
    ];

    protected $casts = [
        'setting_value' => 'array',
    ];

    // Relationship with user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scope to get setting by key
    public function scopeByKey($query, $key)
    {
        return $query->where('setting_key', $key);
    }

    // Scope to get settings by user
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}