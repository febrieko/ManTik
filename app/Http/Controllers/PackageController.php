<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Router;
use App\Services\MikrotikService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class PackageController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->get('type');
        $query = Package::query();
        
        if ($type) {
            $query->where('type', $type);
        }

        $packages = $query->latest()->paginate(15);
        $routers = Router::where('is_active', true)->get();

        return view('packages.index', compact('packages', 'routers', 'type'));
    }

    public function create()
    {
        return view('packages.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:pppoe,hotspot',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'bandwidth_up' => 'nullable|integer',
            'bandwidth_down' => 'nullable|integer',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        Package::create($validated);

        return redirect()->route('packages.index')->with('success', 'Paket berhasil ditambahkan.');
    }

    public function edit(Package $package)
    {
        return view('packages.edit', compact('package'));
    }

    public function update(Request $request, Package $package)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:pppoe,hotspot',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'bandwidth_up' => 'nullable|integer',
            'bandwidth_down' => 'nullable|integer',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $package->update($validated);

        return redirect()->route('packages.index')->with('success', 'Paket berhasil diperbarui.');
    }

    public function destroy(Package $package)
    {
        $package->delete();
        return redirect()->route('packages.index')->with('success', 'Paket berhasil dihapus.');
    }

    public function toggle(Package $package)
    {
        $package->update(['is_active' => !$package->is_active]);
        return back()->with('success', 'Status paket berhasil diubah.');
    }

    public function syncFromRouter(Request $request)
    {
        $request->validate([
            'router_id' => 'required|exists:routers,id',
            'type' => 'required|in:pppoe,hotspot'
        ]);

        $router = Router::findOrFail($request->router_id);
        
        try {
            $service = new MikrotikService($router);
            $profiles = [];
            
            if ($request->type === 'pppoe') {
                $profiles = $service->getPppProfiles();
            } else {
                $profiles = $service->getHotspotProfiles();
            }

            $count = 0;
            foreach ($profiles as $p) {
                if (empty($p['name'])) continue;

                $slug = Str::slug($p['name']);
                
                // Cek apakah paket sudah ada berdasarkan slug dan type
                $package = Package::where('slug', $slug)
                    ->where('type', $request->type)
                    ->first();
                
                $data = [
                    'name' => $p['name'],
                    'slug' => $slug,
                    'type' => $request->type,
                    // Default values if not exist
                    'price' => $package ? $package->price : 0,
                    'duration_days' => $package ? $package->duration_days : 30,
                    'is_active' => true,
                ];

                if ($package) {
                    $package->update($data);
                } else {
                    Package::create($data);
                }
                $count++;
            }

            return back()->with('success', "Berhasil sinkronisasi {$count} profile {$request->type} dari router {$router->name}.");
            
        } catch (\Exception $e) {
            Log::error('Profile sync error: ' . $e->getMessage());
            return back()->with('error', 'Gagal sinkronisasi dari router: ' . $e->getMessage());
        }
    }
}
