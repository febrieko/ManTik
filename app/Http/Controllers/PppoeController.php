<?php

namespace App\Http\Controllers;

use App\Models\PppoeUser;
use App\Models\Router;
use App\Models\Package;
use App\Models\Customer;
use App\Services\MikrotikService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class PppoeController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $routerId = $request->get('router_id');

        if ($user->isTeknisi()) {
            $routers = $user->assignedRouters;
            if ($routerId && !$user->canAccessRouter($routerId)) {
                $routerId = null;
            }
        } else {
            $routers = Router::where('is_active', true)->get();
        }
        
        $query = PppoeUser::with(['router', 'customer', 'package']);
        
        if ($routerId) {
            $query->where('router_id', $routerId);
        } elseif ($user->isTeknisi()) {
            $query->whereIn('router_id', $routers->pluck('id'));
        }

        $pppoeUsers = $query->paginate(15);
        return view('pppoe.index', compact('pppoeUsers', 'routers', 'routerId'));
    }

    public function create()
    {
        $routers = Router::where('is_active', true)->get();
        $packages = Package::where('type', 'pppoe')->where('is_active', true)->get();
        $customers = Customer::where('status', 'active')->get();
        return view('pppoe.create', compact('routers', 'packages', 'customers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'router_id' => 'required|exists:routers,id',
            'username' => [
                'required', 'string', 'max:100',
                Rule::unique('pppoe_users', 'username')
                    ->where('router_id', $request->router_id)
                    ->whereNull('deleted_at')
            ],
            'password' => 'required|string|max:100',
            'package_id' => 'nullable|exists:packages,id',
            'customer_id' => 'nullable|exists:customers,id',
            'service' => 'required|string|in:pppoe,any',
            'local_address' => 'nullable|ip',
            'remote_address' => 'nullable|ip',
            'comment' => 'nullable|string|max:255',
        ]);

        $router = Router::findOrFail($validated['router_id']);
        $package = Package::find($validated['package_id']);
        
        // Setup profile if package is selected
        $validated['profile'] = $package ? $package->slug : 'default';
        $validated['is_disabled'] = false;

        $pppoeUser = PppoeUser::create($validated);

        // Try to sync with mikrotik
        try {
            $service = new MikrotikService($router);
            $service->addPppoeUser([
                'username' => $pppoeUser->username,
                'password' => $validated['password'], // original password
                'service' => $pppoeUser->service,
                'profile' => $pppoeUser->profile,
                'local_address' => $pppoeUser->local_address,
                'remote_address' => $pppoeUser->remote_address,
                'comment' => $pppoeUser->comment,
                'is_disabled' => $pppoeUser->is_disabled,
            ]);
            
            // Sync mikrotik_id back by fetching it
            $mtUsers = $service->getPppoeUsers();
            foreach ($mtUsers as $mtu) {
                if ($mtu['name'] === $pppoeUser->username) {
                    $pppoeUser->update(['mikrotik_id' => $mtu['.id']]);
                    break;
                }
            }
            
            return redirect()->route('pppoe.index')->with('success', 'User PPPoE berhasil ditambahkan dan disinkronisasi ke MikroTik.');
        } catch (\Exception $e) {
            Log::error('Failed to add PPPoE user to Mikrotik: ' . $e->getMessage());
            return redirect()->route('pppoe.index')->with('warning', 'User PPPoE ditambahkan ke database, tetapi gagal sinkronisasi ke MikroTik: ' . $e->getMessage());
        }
    }

    public function edit(PppoeUser $pppoe)
    {
        $routers = Router::where('is_active', true)->get();
        $packages = Package::where('type', 'pppoe')->where('is_active', true)->get();
        $customers = Customer::where('status', 'active')->get();
        return view('pppoe.edit', compact('pppoe', 'routers', 'packages', 'customers'));
    }

    public function update(Request $request, PppoeUser $pppoe)
    {
        $validated = $request->validate([
            'password' => 'nullable|string|max:100',
            'package_id' => 'nullable|exists:packages,id',
            'customer_id' => 'nullable|exists:customers,id',
            'local_address' => 'nullable|ip',
            'remote_address' => 'nullable|ip',
            'comment' => 'nullable|string|max:255',
        ]);

        $package = Package::find($validated['package_id']);
        if ($package) {
            $validated['profile'] = $package->slug;
        }

        // Only update password if provided
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $pppoe->update($validated);

        if ($pppoe->mikrotik_id) {
            try {
                $service = new MikrotikService($pppoe->router);
                $updateData = [
                    'profile' => $pppoe->profile,
                    'comment' => $pppoe->comment,
                    'remote_address' => $pppoe->remote_address,
                ];
                if (isset($validated['password'])) {
                    $updateData['password'] = $validated['password'];
                }
                $service->editPppoeUser($pppoe->mikrotik_id, $updateData);
                return redirect()->route('pppoe.index')->with('success', 'User PPPoE berhasil diperbarui.');
            } catch (\Exception $e) {
                Log::error('Failed to update PPPoE user on Mikrotik: ' . $e->getMessage());
                return redirect()->route('pppoe.index')->with('warning', 'Database diperbarui, tapi sinkronisasi ke MikroTik gagal: ' . $e->getMessage());
            }
        }

        return redirect()->route('pppoe.index')->with('success', 'User PPPoE berhasil diperbarui (Database saja).');
    }

    public function destroy(PppoeUser $pppoe)
    {
        if ($pppoe->mikrotik_id) {
            try {
                $service = new MikrotikService($pppoe->router);
                $service->deletePppoeUser($pppoe->mikrotik_id);
            } catch (\Exception $e) {
                Log::error('Failed to delete PPPoE user from Mikrotik: ' . $e->getMessage());
            }
        }
        $pppoe->delete();
        return redirect()->route('pppoe.index')->with('success', 'User PPPoE berhasil dihapus.');
    }

    public function toggle(PppoeUser $pppoeUser)
    {
        $newState = !$pppoeUser->is_disabled;
        $pppoeUser->update(['is_disabled' => $newState]);

        if ($pppoeUser->mikrotik_id) {
            try {
                $service = new MikrotikService($pppoeUser->router);
                if ($newState) {
                    $service->disablePppoeUser($pppoeUser->mikrotik_id);
                } else {
                    $service->enablePppoeUser($pppoeUser->mikrotik_id);
                }
                return back()->with('success', 'Status PPPoE user berhasil diubah.');
            } catch (\Exception $e) {
                Log::error('Failed to toggle PPPoE user on Mikrotik: ' . $e->getMessage());
                return back()->with('warning', 'Status database diubah, tapi gagal menghubungi MikroTik: ' . $e->getMessage());
            }
        }
        
        return back()->with('success', 'Status PPPoE user berhasil diubah (Database saja).');
    }

    public function syncFromRouter(Router $router)
    {
        try {
            $service = new MikrotikService($router);
            $mtUsers = $service->getPppoeUsers();
            
            $syncedCount = 0;
            foreach ($mtUsers as $mtUser) {
                // Ignore default users or empty names
                if (empty($mtUser['name']) || $mtUser['name'] == 'default') continue;
                
                $user = PppoeUser::withTrashed()
                    ->where('router_id', $router->id)
                    ->where('username', $mtUser['name'])
                    ->first();
                
                $data = [
                    'router_id' => $router->id,
                    'username' => $mtUser['name'],
                    'password' => $mtUser['password'] ?? 'unknown',
                    'profile' => $mtUser['profile'] ?? 'default',
                    'service' => $mtUser['service'] ?? 'pppoe',
                    'local_address' => $mtUser['local-address'] ?? null,
                    'remote_address' => $mtUser['remote-address'] ?? null,
                    'comment' => $mtUser['comment'] ?? null,
                    'is_disabled' => isset($mtUser['disabled']) && $mtUser['disabled'] == 'true',
                    'mikrotik_id' => $mtUser['.id'] ?? null,
                ];

                if ($user) {
                    if ($user->trashed()) {
                        $user->restore();
                    }
                    $user->update($data);
                } else {
                    PppoeUser::create($data);
                }
                $syncedCount++;
            }
            
            return back()->with('success', "Berhasil sinkronisasi {$syncedCount} user PPPoE dari router {$router->name}.");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal sinkronisasi: ' . $e->getMessage());
        }
    }
}
