<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'telegram_chat_id',
        'password',
        'avatar',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function customer()
    {
        return $this->hasOne(Customer::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isTeknisi(): bool
    {
        return $this->hasRole('teknisi');
    }

    public function isCustomer(): bool
    {
        return $this->hasRole('customer');
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        $name = urlencode($this->name);
        return "https://ui-avatars.com/api/?name={$name}&background=00d4ff&color=fff&bold=true";
    }

    public function assignedRouters(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Router::class, 'user_router_assignments')
                    ->withPivot('assigned_by', 'note')
                    ->withTimestamps();
    }

    public function canAccessRouter(int $routerId): bool
    {
        if ($this->isSuperAdmin() || $this->isAdmin()) {
            return true;
        }
        if ($this->isTeknisi()) {
            return $this->assignedRouters()->where('router_id', $routerId)->exists();
        }
        return false;
    }

    /**
     * Mutator kolom phone: otomatis normalisasi penulisan 0 atau 62 menjadi format standar 62...
     */
    public function setPhoneAttribute($value): void
    {
        if (empty($value)) {
            $this->attributes['phone'] = null;
            return;
        }

        $clean = preg_replace('/\D/', '', $value);
        if (str_starts_with($clean, '0')) {
            $clean = '62' . substr($clean, 1);
        } elseif (str_starts_with($clean, '8') && strlen($clean) >= 9) {
            $clean = '62' . $clean;
        }

        $this->attributes['phone'] = $clean ?: null;
    }
}

