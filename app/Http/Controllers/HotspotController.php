<?php

namespace App\Http\Controllers;

use App\Models\HotspotUser;
use App\Models\HotspotVoucher;
use App\Models\Router;
use App\Models\Package;
use App\Services\MikrotikService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class HotspotController extends Controller
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
        
        $query = HotspotUser::with(['router', 'package']);
        
        if ($routerId) {
            $query->where('router_id', $routerId);
        } elseif ($user->isTeknisi()) {
            $query->whereIn('router_id', $routers->pluck('id'));
        }

        $hotspotUsers = $query->paginate(15);
        return view('hotspot.index', compact('hotspotUsers', 'routers', 'routerId'));
    }

    public function create()
    {
        $routers = Router::where('is_active', true)->get();
        $packages = Package::where('type', 'hotspot')->where('is_active', true)->get();
        return view('hotspot.create', compact('routers', 'packages'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'router_id' => 'required|exists:routers,id',
            'username' => 'required|string|max:100',
            'password' => 'required|string|max:100',
            'package_id' => 'nullable|exists:packages,id',
            'limit_uptime' => 'nullable|string',
            'limit_bytes_total' => 'nullable|integer',
            'comment' => 'nullable|string|max:255',
        ]);

        $router = Router::findOrFail($validated['router_id']);
        $package = Package::find($validated['package_id']);
        
        $validated['profile'] = $package ? $package->slug : 'default';
        $validated['is_disabled'] = false;

        $hotspotUser = HotspotUser::create($validated);

        try {
            $service = new MikrotikService($router);
            
            $userData = [
                'server' => 'all',
                'name' => $hotspotUser->username,
                'password' => $validated['password'],
                'profile' => $hotspotUser->profile,
                'comment' => $hotspotUser->comment,
            ];

            if ($hotspotUser->limit_uptime) {
                $userData['limit-uptime'] = $hotspotUser->limit_uptime;
            }
            if ($hotspotUser->limit_bytes_total) {
                $userData['limit-bytes-total'] = $hotspotUser->limit_bytes_total;
            }

            $service->addHotspotUser($userData);
            
            return redirect()->route('hotspot.index')->with('success', 'User Hotspot berhasil ditambahkan dan disinkronisasi ke MikroTik.');
        } catch (\Exception $e) {
            Log::error('Failed to add Hotspot user to Mikrotik: ' . $e->getMessage());
            return redirect()->route('hotspot.index')->with('warning', 'User Hotspot ditambahkan ke database, tetapi gagal sinkronisasi ke MikroTik: ' . $e->getMessage());
        }
    }

    public function destroy(HotspotUser $hotspot)
    {
        try {
            $service = new MikrotikService($hotspot->router);
            // In a real app we'd fetch the .id first by name or store .id
            // For now, assume MikrotikService has deleteHotspotUserByName
            $service->deleteHotspotUserByName($hotspot->username);
        } catch (\Exception $e) {
            Log::error('Failed to delete Hotspot user from Mikrotik: ' . $e->getMessage());
        }
        
        $hotspot->delete();
        return redirect()->route('hotspot.index')->with('success', 'User Hotspot berhasil dihapus.');
    }

    public function edit(HotspotUser $hotspot)
    {
        $routers = Router::where('is_active', true)->get();
        $packages = Package::where('type', 'hotspot')->where('is_active', true)->get();
        return view('hotspot.edit', compact('hotspot', 'routers', 'packages'));
    }

    public function update(Request $request, HotspotUser $hotspot)
    {
        $validated = $request->validate([
            'password' => 'nullable|string|max:100',
            'package_id' => 'nullable|exists:packages,id',
            'limit_uptime' => 'nullable|string',
            'limit_bytes_total' => 'nullable|integer',
            'comment' => 'nullable|string|max:255',
        ]);

        $package = Package::find($validated['package_id']);
        if ($package) {
            $validated['profile'] = $package->slug;
        }

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $hotspot->update($validated);

        try {
            $service = new MikrotikService($hotspot->router);
            
            $updateData = [
                'profile' => $hotspot->profile,
                'comment' => $hotspot->comment,
            ];
            
            if (isset($validated['password'])) {
                $updateData['password'] = $validated['password'];
            }
            if (isset($validated['limit_uptime'])) {
                $updateData['limit-uptime'] = $validated['limit_uptime'];
            }
            if (isset($validated['limit_bytes_total'])) {
                $updateData['limit-bytes-total'] = $validated['limit_bytes_total'];
            }

            // Fallback for edit hotspot
            // Normally you would find the user by ID in mikrotik, but here we can just delete and add again or set using name
            $service->editHotspotUserByName($hotspot->username, $updateData);
            
            return redirect()->route('hotspot.index')->with('success', 'User Hotspot berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('Failed to update Hotspot user on Mikrotik: ' . $e->getMessage());
            return redirect()->route('hotspot.index')->with('warning', 'Database diperbarui, tapi sinkronisasi ke MikroTik gagal: ' . $e->getMessage());
        }
    }

    public function toggle(HotspotUser $hotspot)
    {
        $newState = !$hotspot->is_disabled;
        $hotspot->update(['is_disabled' => $newState]);

        try {
            $service = new MikrotikService($hotspot->router);
            if ($newState) {
                $service->disableHotspotUserByName($hotspot->username);
            } else {
                $service->enableHotspotUserByName($hotspot->username);
            }
            return back()->with('success', 'Status Hotspot user berhasil diubah.');
        } catch (\Exception $e) {
            Log::error('Failed to toggle Hotspot user on Mikrotik: ' . $e->getMessage());
            return back()->with('warning', 'Status database diubah, tapi gagal di router: ' . $e->getMessage());
        }
    }

    public function syncFromRouter(Router $router)
    {
        try {
            $service = new MikrotikService($router);
            $mtUsers = $service->getHotspotUsers();
            
            $count = 0;
            foreach ($mtUsers as $u) {
                if (empty($u['name']) || $u['name'] === 'default-trial') continue;

                $user = HotspotUser::withTrashed()
                    ->where('router_id', $router->id)
                    ->where('username', $u['name'])
                    ->first();
                
                $data = [
                    'router_id' => $router->id,
                    'username' => $u['name'],
                    'password' => $u['password'] ?? '',
                    'profile' => $u['profile'] ?? 'default',
                    'limit_uptime' => $u['limit-uptime'] ?? null,
                    'limit_bytes_total' => $u['limit-bytes-total'] ?? null,
                    'comment' => $u['comment'] ?? null,
                    'is_disabled' => isset($u['disabled']) && $u['disabled'] == 'true',
                ];

                if ($user) {
                    if ($user->trashed()) {
                        $user->restore();
                    }
                    $user->update($data);
                } else {
                    HotspotUser::create($data);
                }
                $count++;
            }
            return back()->with('success', "Berhasil sinkronisasi {$count} user Hotspot dari router {$router->name}.");
        } catch (\Exception $e) {
            Log::error('Hotspot sync error: ' . $e->getMessage());
            return back()->with('error', 'Gagal sinkronisasi dari router: ' . $e->getMessage());
        }
    }

    // ==========================================
    // VOUCHER MANAGEMENT
    // ==========================================

    public function vouchers(Request $request)
    {
        $routerId = $request->get('router_id');
        $routers = Router::where('is_active', true)->get();
        
        $query = HotspotVoucher::with(['router', 'package'])->orderByDesc('created_at');
        
        if ($routerId) {
            $query->where('router_id', $routerId);
        }

        $vouchers = $query->paginate(30);
        return view('hotspot.vouchers', compact('vouchers', 'routers', 'routerId'));
    }

    public function generateVouchers()
    {
        $routers = Router::where('is_active', true)->get();
        $packages = Package::where('type', 'hotspot')->where('is_active', true)->get();
        return view('hotspot.generate', compact('routers', 'packages'));
    }

    public function storeVouchers(Request $request)
    {
        $validated = $request->validate([
            'router_id' => 'required|exists:routers,id',
            'package_id' => 'required|exists:packages,id',
            'qty' => 'required|integer|min:1|max:500',
            'mode' => 'required|in:up,vc', 
            'length' => 'required|integer|min:4|max:12',
            'prefix' => 'nullable|string|max:4',
            'time_limit' => 'nullable|string', 
            'data_limit' => 'nullable|integer', 
        ]);

        $router = Router::findOrFail($validated['router_id']);
        $package = Package::findOrFail($validated['package_id']);
        
        $qty = $validated['qty'];
        $vouchersCreated = [];

        DB::beginTransaction();
        try {
            for ($i = 0; $i < $qty; $i++) {
                $code = $validated['prefix'] . Str::random($validated['length']);
                $code = strtolower($code);
                
                $username = $code;
                $password = $validated['mode'] === 'vc' ? $username : strtolower(Str::random($validated['length']));

                $voucher = HotspotVoucher::create([
                    'router_id' => $router->id,
                    'package_id' => $package->id,
                    'code' => $code,
                    'username' => $username,
                    'password' => $password,
                    'profile' => $package->slug,
                    'is_used' => false,
                ]);

                $vouchersCreated[] = $voucher;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal generate voucher di database: ' . $e->getMessage());
        }

        try {
            $service = new MikrotikService($router);
            $successCount = 0;

            foreach ($vouchersCreated as $v) {
                $userData = [
                    'server' => 'all',
                    'name' => $v->username,
                    'password' => $v->password,
                    'profile' => $v->profile,
                    'comment' => 'Voucher ' . config('app.name'),
                ];

                if (!empty($validated['time_limit'])) {
                    $userData['limit-uptime'] = $validated['time_limit'];
                }
                if (!empty($validated['data_limit'])) {
                    $userData['limit-bytes-total'] = $validated['data_limit'];
                }

                $service->addHotspotUser($userData);
                $successCount++;
            }

            return redirect()->route('hotspot.vouchers')->with('success', "Berhasil membuat dan sinkronisasi {$successCount} voucher ke MikroTik.");
        } catch (\Exception $e) {
            Log::error('Voucher sync error: ' . $e->getMessage());
            return redirect()->route('hotspot.vouchers')->with('warning', "Voucher berhasil dibuat di database, tapi gagal sinkronisasi: " . $e->getMessage());
        }
    }

    public function printBulk(Request $request)
    {
        $voucherIds = $request->input('vouchers', []);
        if (empty($voucherIds)) {
            return back()->with('error', 'Tidak ada voucher yang dipilih.');
        }

        $vouchers = HotspotVoucher::with('package')->whereIn('id', $voucherIds)->get();
        return view('hotspot.print', compact('vouchers'));
    }
}
