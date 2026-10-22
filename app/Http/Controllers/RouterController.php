<?php

namespace App\Http\Controllers;

use App\Models\Router;
use App\Services\MikrotikService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RouterController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        if ($user->isTeknisi()) {
            $routers = $user->assignedRouters()->with('latestStat')->withCount(['pppoeUsers', 'hotspotUsers', 'queues'])->get();
        } else {
            $routers = Router::with('latestStat')->withCount(['pppoeUsers', 'hotspotUsers', 'queues'])->get();
        }

        return view('routers.index', compact('routers'));
    }

    public function create()
    {
        return view('routers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'ssid'        => 'nullable|string|max:100',
            'host'        => 'required|string|max:100',
            'port'        => 'required|integer|min:1|max:65535',
            'username'    => 'required|string|max:50',
            'password'    => 'required|string',
            'description' => 'nullable|string|max:255',
            'location'    => 'nullable|string|max:100',
            'brand'       => 'nullable|string|max:50',
            'model'       => 'nullable|string|max:50',
            'ros_version' => 'required|in:v6,v7',
        ]);

        $router = Router::create($validated);

        // Test connection
        $service = new MikrotikService($router);
        try {
            $service->getSystemIdentity(); // Test query to verify auth works
            $router->update(['status' => 'online', 'last_checked_at' => now()]);
            return redirect()->route('routers.index')->with('success', "Router {$router->name} berhasil ditambahkan dan terhubung!");
        } catch (\Exception $e) {
            $router->update(['status' => 'offline']);
            return redirect()->route('routers.index')->with('success', "Router {$router->name} berhasil ditambahkan (tidak dapat terhubung: " . $e->getMessage() . ").");
        }
    }

    public function show(Router $router)
    {
        $router->load('latestStat', 'pppoeUsers', 'hotspotUsers', 'queues');
        
        // Get stats from last 5 minutes
        $stats = $router->stats()->where('recorded_at', '>=', now()->subMinutes(5))->orderBy('recorded_at')->get();

        return view('routers.show', compact('router', 'stats'));
    }

    public function edit(Router $router)
    {
        return view('routers.edit', compact('router'));
    }

    public function update(Request $request, Router $router)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'ssid'        => 'nullable|string|max:100',
            'host'        => 'required|string|max:100',
            'port'        => 'required|integer|min:1|max:65535',
            'username'    => 'required|string|max:50',
            'password'    => 'nullable|string',
            'description' => 'nullable|string|max:255',
            'location'    => 'nullable|string|max:100',
            'brand'       => 'nullable|string|max:50',
            'model'       => 'nullable|string|max:50',
            'ros_version' => 'required|in:v6,v7',
            'is_active'   => 'boolean',
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $router->update($validated);
        return redirect()->route('routers.index')->with('success', "Router {$router->name} berhasil diperbarui!");
    }

    public function destroy(Router $router)
    {
        $router->delete();
        return redirect()->route('routers.index')->with('success', "Router {$router->name} berhasil dihapus.");
    }

    public function testConnection(Router $router)
    {
        $monitorService = app(\App\Services\RouterMonitorService::class);
        $result = $monitorService->checkRouter($router);

        if ($result['status'] === 'online') {
            try {
                $service = new MikrotikService($router);
                $info = $service->connect() ? $service->getSystemResource() : [];
                $identity = $service->getSystemIdentity();

                return response()->json([
                    'success' => true,
                    'message' => 'Koneksi berhasil! (' . $result['response_time_ms'] . 'ms)',
                    'data'    => [
                        'identity' => $identity['name'] ?? 'Unknown',
                        'uptime'   => $info['uptime'] ?? 'N/A',
                        'version'  => $info['version'] ?? 'N/A',
                        'cpu'      => $info['cpu-load'] ?? '0',
                    ]
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => true,
                    'message' => 'Koneksi berhasil!',
                    'data'    => ['identity' => $router->name, 'uptime' => '-', 'version' => '-', 'cpu' => '0']
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Gagal terhubung: ' . $result['reason']
        ]);
    }
}
