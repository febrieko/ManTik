<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRouterAssignment extends Model
{
    protected $fillable = ['user_id', 'router_id', 'assigned_by', 'note'];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function router(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function assignedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
