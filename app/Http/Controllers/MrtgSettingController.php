<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Router;
use App\Models\RouterInterfaceSetting;
use App\Models\TrafficLog;
use App\Services\MikrotikService;
use Illuminate\Support\Facades\Cache;

class MrtgSettingController extends Controller
{
    public function index(Router $router)
    {
        // Hanya super-admin & admin
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->isAdmin(), 403);

        $settings = RouterInterfaceSetting::where('router_id', $router->id)
            ->orderBy('sort_order')->orderBy('interface_name')
            ->get()->keyBy('interface_name');

        $knownInterfaces = TrafficLog::where('router_id', $router->id)
            ->select('interface')->distinct()->pluck('interface');

        $routers = Router::where('is_active', true)->get();

        return view('traffic.mrtg-settings', compact(
            'router', 'settings', 'knownInterfaces', 'routers'
        ));
    }

    public function sync(Router $router)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->isAdmin(), 403);

        try {
            $service = new MikrotikService($router);
            $interfaces = $service->getInterfaces();

            $count = 0;
            foreach ($interfaces as $iface) {
                if (!isset($iface['name'])) continue;

                RouterInterfaceSetting::firstOrCreate(
                    ['router_id' => $router->id, 'interface_name' => $iface['name']],
                    ['is_visible' => true, 'sort_order' => 0]
                );
                $count++;
            }

            Cache::forget("mrtg_visible_interfaces_{$router->id}");

            return back()->with('success', "Berhasil sync {$count} interface dari router.");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal sync: ' . $e->getMessage());
        }
    }

    public function save(Router $router, Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->isAdmin(), 403);

        $data = $request->input('interfaces', []);

        foreach ($data as $ifaceName => $values) {
            RouterInterfaceSetting::updateOrCreate(
                ['router_id' => $router->id, 'interface_name' => $ifaceName],
                [
                    'is_visible'   => isset($values['visible']) && $values['visible'] === '1',
                    'display_name' => $values['display_name'] ?? null,
                    'description'  => $values['description'] ?? null,
                    'sort_order'   => (int) ($values['sort_order'] ?? 0),
                ]
            );
        }

        Cache::forget("mrtg_visible_interfaces_{$router->id}");

        return back()->with('success', 'Pengaturan interface berhasil disimpan.');
    }

    public function toggle(Router $router, RouterInterfaceSetting $setting)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->isAdmin(), 403);

        if ($setting->router_id !== $router->id) {
            return response()->json(['success' => false], 403);
        }

        $setting->update(['is_visible' => !$setting->is_visible]);
        Cache::forget("mrtg_visible_interfaces_{$router->id}");

        return response()->json([
            'success'    => true,
            'is_visible' => $setting->is_visible,
        ]);
    }

    public function reorder(Router $router, Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->isAdmin(), 403);

        $items = $request->input('order', []);
        foreach ($items as $item) {
            RouterInterfaceSetting::where('id', $item['id'])
                ->where('router_id', $router->id)
                ->update(['sort_order' => $item['sort_order']]);
        }

        Cache::forget("mrtg_visible_interfaces_{$router->id}");

        return response()->json(['success' => true]);
    }
}
