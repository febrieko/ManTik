<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RouterInterfaceSetting extends Model
{
    protected $fillable = [
        'router_id', 'interface_name', 'display_name',
        'is_visible', 'sort_order', 'description'
    ];

    protected $casts = [
        'is_visible'  => 'boolean',
        'sort_order'  => 'integer',
    ];

    public function router(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function getEffectiveNameAttribute(): string
    {
        return $this->display_name ?: $this->interface_name;
    }
}
