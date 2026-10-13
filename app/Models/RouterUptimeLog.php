<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class RouterUptimeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'router_id',
        'status',
        'started_at',
        'ended_at',
        'duration_seconds',
        'response_time_ms',
        'reason',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'duration_seconds' => 'integer',
        'response_time_ms' => 'integer',
    ];

    public function router()
    {
        return $this->belongsTo(Router::class);
    }

    /**
     * Check if status is still ongoing
     */
    public function isOngoing(): bool
    {
        return $this->ended_at === null;
    }

    /**
     * Get actual duration in seconds (computed if still ongoing)
     */
    public function getDurationSecondsActualAttribute(): int
    {
        if ($this->ended_at !== null && $this->duration_seconds !== null && $this->duration_seconds > 0) {
            return (int) $this->duration_seconds;
        }

        if ($this->started_at) {
            $endTimestamp = $this->ended_at ? $this->ended_at->timestamp : now()->timestamp;
            return max(0, $endTimestamp - $this->started_at->timestamp);
        }

        return 0;
    }

    /**
     * Format duration into readable Indonesian text
     */
    public function getDurationFormattedAttribute(): string
    {
        $seconds = $this->duration_seconds_actual;
        return self::formatDuration($seconds);
    }

    /**
     * Static helper to format seconds to human-readable string
     */
    public static function formatDuration(int $seconds): string
    {
        if ($seconds < 1) {
            return '< 1 detik';
        }

        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $remainingSeconds = $seconds % 60;

        $parts = [];
        if ($days > 0) {
            $parts[] = "{$days} hari";
        }
        if ($hours > 0) {
            $parts[] = "{$hours} jam";
        }
        if ($minutes > 0) {
            $parts[] = "{$minutes} menit";
        }
        if (empty($parts) || ($days == 0 && $hours == 0 && $remainingSeconds > 0)) {
            $parts[] = "{$remainingSeconds} detik";
        }

        return implode(' ', array_slice($parts, 0, 3));
    }
}
