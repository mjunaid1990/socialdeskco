<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpamFilter extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'pattern',
        'reason',
        'is_active',
        'match_count',
        'last_matched_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_matched_at' => 'datetime',
    ];

    // Relationship with user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scope for active filters
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope for specific type
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // Increment match count and update last matched timestamp
    public function recordMatch()
    {
        $this->increment('match_count');
        $this->update(['last_matched_at' => now()]);
    }

    // Get spam statistics
    public static function getStats()
    {
        return [
            'total_filters' => self::count(),
            'active_filters' => self::active()->count(),
            'spam_keywords' => self::byType('spam_keyword')->active()->count(),
            'blacklisted_users' => self::byType('blacklisted_user')->active()->count(),
            'suspicious_patterns' => self::byType('suspicious_pattern')->active()->count(),
            'total_matches' => self::sum('match_count'),
        ];
    }
}
