<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Queue extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'router_id', 'name', 'target', 'dst', 'max_limit',
        'burst_limit', 'burst_threshold', 'priority',
        'type', 'parent', 'is_disabled', 'comment', 'mikrotik_id',
    ];

    protected $casts = [
        'is_disabled' => 'boolean',
        'priority' => 'integer',
    ];

    public function router()
    {
        return $this->belongsTo(Router::class);
    }

    public function getUploadLimitAttribute(): string
    {
        if (!$this->max_limit) return '0';
        return explode('/', $this->max_limit)[0] ?? '0';
    }

    public function getDownloadLimitAttribute(): string
    {
        if (!$this->max_limit) return '0';
        return explode('/', $this->max_limit)[1] ?? '0';
    }
}
