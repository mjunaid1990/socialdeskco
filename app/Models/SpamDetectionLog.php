<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpamDetectionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'engagement_id',
        'detection_type',
        'reason',
        'metadata',
        'actor_username',
        'actor_platform_id',
        'platform',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    // Relationship with engagement
    public function engagement()
    {
        return $this->belongsTo(Engagement::class);
    }

    // Scope for detection type
    public function scopeByType($query, string $type)
    {
        return $query->where('detection_type', $type);
    }

    // Scope for recent detections
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    // Get detection statistics
    public static function getDetectionStats(int $hours = 24)
    {
        $query = self::recent($hours);

        return [
            'total_detections' => $query->count(),
            'by_type' => $query->selectRaw('detection_type, COUNT(*) as count')
                ->groupBy('detection_type')
                ->pluck('count', 'detection_type')
                ->toArray(),
            'by_platform' => $query->selectRaw('platform, COUNT(*) as count')
                ->groupBy('platform')
                ->pluck('count', 'platform')
                ->toArray(),
            'top_actors' => $query->selectRaw('actor_platform_id, COUNT(*) as count')
                ->groupBy('actor_platform_id')
                ->orderByDesc('count')
                ->limit(10)
                ->get(),
        ];
    }
}
