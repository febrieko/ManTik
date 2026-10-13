<?php

namespace App\Policies;

use App\Models\HotspotUser;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class HotspotPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view hotspot');
    }

    public function view(User $user, HotspotUser $hotspotUser): bool
    {
        return $user->can('view hotspot') && $user->canAccessRouter($hotspotUser->router_id);
    }

    public function create(User $user): bool
    {
        return $user->can('create hotspot') && !$user->isTeknisi();
    }

    public function update(User $user, HotspotUser $hotspotUser): bool
    {
        if (!$user->can('edit hotspot')) return false;
        return $user->canAccessRouter($hotspotUser->router_id);
    }

    public function delete(User $user, HotspotUser $hotspotUser): bool
    {
        if ($user->isTeknisi()) return false;
        return $user->can('delete hotspot') && $user->canAccessRouter($hotspotUser->router_id);
    }

    public function restore(User $user, HotspotUser $hotspotUser): bool
    {
        if ($user->isTeknisi()) return false;
        return $user->can('delete hotspot') && $user->canAccessRouter($hotspotUser->router_id);
    }

    public function forceDelete(User $user, HotspotUser $hotspotUser): bool
    {
        if ($user->isTeknisi()) return false;
        return $user->can('delete hotspot') && $user->canAccessRouter($hotspotUser->router_id);
    }
}
