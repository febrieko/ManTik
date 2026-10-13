<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Router;
use App\Models\UserRouterAssignment;

class UserRouterAssignmentController extends Controller
{
    public function index(User $user)
    {
        // Pastikan hanya admin yang bisa akses
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->isAdmin(), 403);

        // Pastikan yang diatur aksesnya adalah teknisi
        abort_unless($user->isTeknisi(), 403, 'Hanya user dengan role Teknisi yang dapat dikonfigurasi akses routernya.');

        $assignedRouters = $user->assignedRouters;
        $allRouters = Router::where('is_active', true)->get();

        return view('users.router-assignments', compact('user', 'assignedRouters', 'allRouters'));
    }

    public function assign(Request $request, User $user)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->isAdmin(), 403);
        abort_unless($user->isTeknisi(), 403);

        $request->validate([
            'router_id' => 'required|exists:routers,id',
            'note' => 'nullable|string|max:255',
        ]);

        UserRouterAssignment::firstOrCreate(
            ['user_id' => $user->id, 'router_id' => $request->router_id],
            ['assigned_by' => auth()->id(), 'note' => $request->note]
        );

        return back()->with('success', 'Akses router berhasil diberikan.');
    }

    public function revoke(User $user, Router $router)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->isAdmin(), 403);

        UserRouterAssignment::where('user_id', $user->id)
            ->where('router_id', $router->id)
            ->delete();

        return back()->with('success', 'Akses router berhasil dicabut.');
    }
}
