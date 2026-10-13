<?php

namespace App\Policies;

use App\Models\PppoeUser;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PppoePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view pppoe');
    }

    public function view(User $user, PppoeUser $pppoeUser): bool
    {
        return $user->can('view pppoe') && $user->canAccessRouter($pppoeUser->router_id);
    }

    public function create(User $user): bool
    {
        return $user->can('create pppoe') && !$user->isTeknisi();
    }

    public function update(User $user, PppoeUser $pppoeUser): bool
    {
        if (!$user->can('edit pppoe')) return false;
        return $user->canAccessRouter($pppoeUser->router_id);
    }

    public function delete(User $user, PppoeUser $pppoeUser): bool
    {
        if ($user->isTeknisi()) return false;
        return $user->can('delete pppoe') && $user->canAccessRouter($pppoeUser->router_id);
    }

    public function restore(User $user, PppoeUser $pppoeUser): bool
    {
        if ($user->isTeknisi()) return false;
        return $user->can('delete pppoe') && $user->canAccessRouter($pppoeUser->router_id);
    }

    public function forceDelete(User $user, PppoeUser $pppoeUser): bool
    {
        if ($user->isTeknisi()) return false;
        return $user->can('delete pppoe') && $user->canAccessRouter($pppoeUser->router_id);
    }
}
