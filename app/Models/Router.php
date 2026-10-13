<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Router extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'host', 'port', 'username', 'password', 'description',
        'location', 'brand', 'model', 'is_active', 'status', 'last_checked_at', 'ros_version'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_checked_at' => 'datetime',
        'port' => 'integer',
    ];

    protected $hidden = ['password'];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::deleting(function (Router $router) {
            if (method_exists($router, 'isForceDeleting') && $router->isForceDeleting()) {
                $router->pppoeUsers()->forceDelete();
                $router->hotspotUsers()->forceDelete();
                $router->hotspotVouchers()->forceDelete();
            } else {
                $router->pppoeUsers()->delete();
                $router->hotspotUsers()->delete();
                $router->hotspotVouchers()->delete();
            }
        });

        static::restoring(function (Router $router) {
            $router->pppoeUsers()->restore();
            $router->hotspotUsers()->restore();
            $router->hotspotVouchers()->restore();
        });
    }

    public function stats()
    {
        return $this->hasMany(RouterStat::class);
    }

    public function latestStat()
    {
        return $this->hasOne(RouterStat::class)->latestOfMany();
    }

    public function pppoeUsers()
    {
        return $this->hasMany(PppoeUser::class);
    }

    public function hotspotUsers()
    {
        return $this->hasMany(HotspotUser::class);
    }

    public function hotspotVouchers()
    {
        return $this->hasMany(HotspotVoucher::class);
    }

    public function queues()
    {
        return $this->hasMany(Queue::class);
    }

    public function notificationLogs()
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_router_assignments')
                    ->withPivot('assigned_by', 'note')
                    ->withTimestamps();
    }

    public function isOnline(): bool
    {
        return $this->status === 'online';
    }

    public function interfaceSettings()
    {
        return $this->hasMany(RouterInterfaceSetting::class)->orderBy('sort_order');
    }

    public function visibleInterfaces()
    {
        return $this->hasMany(RouterInterfaceSetting::class)
                    ->where('is_visible', true)
                    ->orderBy('sort_order');
    }

    public function uptimeLogs()
    {
        return $this->hasMany(RouterUptimeLog::class)->orderByDesc('started_at');
    }

    public function latestUptimeLog()
    {
        return $this->hasOne(RouterUptimeLog::class)->latestOfMany('started_at');
    }

    public function currentUptimeLog()
    {
        return $this->hasOne(RouterUptimeLog::class)->whereNull('ended_at')->latestOfMany('started_at');
    }

    public function getCurrentStatusDurationFormatted(): string
    {
        $log = $this->currentUptimeLog ?? $this->latestUptimeLog;
        if ($log) {
            return $log->duration_formatted;
        }

        if ($this->last_checked_at) {
            $seconds = max(0, (int) now()->diffInSeconds($this->last_checked_at));
            return RouterUptimeLog::formatDuration($seconds);
        }

        return '-';
    }

    /**
     * Calculate uptime percentage over the past $days days
     */
    public function getUptimePercentage(int $days = 30): float
    {
        $since = now()->subDays($days);
        $sinceTimestamp = $since->timestamp;
        $nowTimestamp = now()->timestamp;

        $logs = $this->uptimeLogs()
            ->where(function ($query) use ($since) {
                $query->where('started_at', '>=', $since)
                      ->orWhereNull('ended_at')
                      ->orWhere('ended_at', '>=', $since);
            })
            ->get();

        if ($logs->isEmpty()) {
            return $this->status === 'online' ? 100.0 : 0.0;
        }

        // Find earliest start timestamp among retrieved logs
        $firstStartTimestamp = null;
        foreach ($logs as $log) {
            $ts = $log->started_at ? $log->started_at->timestamp : null;
            if ($ts !== null && ($firstStartTimestamp === null || $ts < $firstStartTimestamp)) {
                $firstStartTimestamp = $ts;
            }
        }

        $effectiveStartTimestamp = $firstStartTimestamp !== null 
            ? max($sinceTimestamp, $firstStartTimestamp) 
            : $sinceTimestamp;

        $totalWindowSeconds = max(1, $nowTimestamp - $effectiveStartTimestamp);
        $totalOnlineSeconds = 0;

        foreach ($logs as $log) {
            if ($log->status !== 'online') {
                continue;
            }

            $startTs = $log->started_at ? max($effectiveStartTimestamp, $log->started_at->timestamp) : $effectiveStartTimestamp;
            $endTs = $log->ended_at ? min($nowTimestamp, $log->ended_at->timestamp) : $nowTimestamp;

            if ($endTs > $startTs) {
                $totalOnlineSeconds += ($endTs - $startTs);
            }
        }

        $percentage = ($totalOnlineSeconds / $totalWindowSeconds) * 100;
        return round(min(100.0, max(0.0, $percentage)), 1);
    }
}
