<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use App\Models\Router;
use App\Services\MikrotikService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class QueueController extends Controller
{
    public function index(Request $request)
    {
        $routerId = $request->get('router_id');
        $routers = Router::where('is_active', true)->get();
        
        $query = Queue::with('router');
        if ($routerId) {
            $query->where('router_id', $routerId);
        }

        $queues = $query->paginate(20);
        return view('queue.index', compact('queues', 'routers', 'routerId'));
    }

    public function create()
    {
        $routers = Router::where('is_active', true)->get();
        return view('queue.create', compact('routers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'router_id' => 'required|exists:routers,id',
            'name' => 'required|string|max:100',
            'target' => 'required|string|max:100', // IP address or subnet
            'max_limit_tx' => 'required|string', // e.g. 1M
            'max_limit_rx' => 'required|string', // e.g. 1M
            'type' => 'required|in:simple,tree',
            'comment' => 'nullable|string|max:255',
        ]);

        $router = Router::findOrFail($validated['router_id']);
        
        // Format for mikrotik: max-limit=rx/tx (Download/Upload)
        $maxLimit = $validated['max_limit_rx'] . '/' . $validated['max_limit_tx'];

        $queue = Queue::create([
            'router_id' => $router->id,
            'name' => $validated['name'],
            'target' => $validated['target'],
            'max_limit' => $maxLimit,
            'type' => $validated['type'],
            'comment' => $validated['comment'],
            'is_disabled' => false,
        ]);

        try {
            $service = new MikrotikService($router);
            
            if ($queue->type === 'simple') {
                $service->addSimpleQueue([
                    'name' => $queue->name,
                    'target' => $queue->target,
                    'max-limit' => $queue->max_limit,
                    'comment' => $queue->comment,
                    'disabled' => 'no'
                ]);
            }
            
            // Re-sync to get ID
            $this->syncRouterQueues($router);

            return redirect()->route('queue.index')->with('success', 'Queue berhasil dibuat dan disinkronisasi.');
        } catch (\Exception $e) {
            Log::error('Failed to add queue: ' . $e->getMessage());
            return redirect()->route('queue.index')->with('warning', 'Queue disimpan di database, namun gagal dikirim ke router: ' . $e->getMessage());
        }
    }

    public function edit(Queue $queue)
    {
        $routers = Router::where('is_active', true)->get();
        
        // Parse max-limit for form
        $limits = explode('/', $queue->max_limit);
        $rx = $limits[0] ?? '';
        $tx = $limits[1] ?? '';

        return view('queue.edit', compact('queue', 'routers', 'rx', 'tx'));
    }

    public function update(Request $request, Queue $queue)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'target' => 'required|string|max:100',
            'max_limit_tx' => 'required|string',
            'max_limit_rx' => 'required|string',
            'comment' => 'nullable|string|max:255',
        ]);

        $maxLimit = $validated['max_limit_rx'] . '/' . $validated['max_limit_tx'];

        $queue->update([
            'name' => $validated['name'],
            'target' => $validated['target'],
            'max_limit' => $maxLimit,
            'comment' => $validated['comment'],
        ]);

        if ($queue->mikrotik_id) {
            try {
                $service = new MikrotikService($queue->router);
                if ($queue->type === 'simple') {
                    $service->editSimpleQueue($queue->mikrotik_id, [
                        'name' => $queue->name,
                        'target' => $queue->target,
                        'max-limit' => $queue->max_limit,
                        'comment' => $queue->comment,
                    ]);
                }
                return redirect()->route('queue.index')->with('success', 'Queue berhasil diperbarui.');
            } catch (\Exception $e) {
                return redirect()->route('queue.index')->with('warning', 'Database terupdate, namun router gagal merespon: ' . $e->getMessage());
            }
        }

        return redirect()->route('queue.index')->with('success', 'Queue berhasil diperbarui (Database saja).');
    }

    public function destroy(Queue $queue)
    {
        if ($queue->mikrotik_id) {
            try {
                $service = new MikrotikService($queue->router);
                if ($queue->type === 'simple') {
                    $service->deleteSimpleQueue($queue->mikrotik_id);
                }
            } catch (\Exception $e) {
                Log::error('Delete queue error: ' . $e->getMessage());
            }
        }
        $queue->delete();
        return redirect()->route('queue.index')->with('success', 'Queue berhasil dihapus.');
    }

    public function toggle(Queue $queue)
    {
        $newState = !$queue->is_disabled;
        $queue->update(['is_disabled' => $newState]);

        if ($queue->mikrotik_id) {
            try {
                $service = new MikrotikService($queue->router);
                if ($queue->type === 'simple') {
                    if ($newState) {
                        $service->disableSimpleQueue($queue->mikrotik_id);
                    } else {
                        $service->enableSimpleQueue($queue->mikrotik_id);
                    }
                }
                return back()->with('success', 'Status Queue berhasil diubah.');
            } catch (\Exception $e) {
                return back()->with('warning', 'Status database diubah, tapi gagal di router: ' . $e->getMessage());
            }
        }
        return back()->with('success', 'Status berhasil diubah (Database saja).');
    }

    public function syncFromRouter(Router $router)
    {
        try {
            $count = $this->syncRouterQueues($router);
            return back()->with('success', "Berhasil sinkronisasi {$count} rule Queue dari router {$router->name}.");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal sinkronisasi: ' . $e->getMessage());
        }
    }

    private function syncRouterQueues(Router $router)
    {
        $service = new MikrotikService($router);
        $mtQueues = $service->getSimpleQueues();
        
        $count = 0;
        foreach ($mtQueues as $q) {
            if (empty($q['name'])) continue;

            $queue = Queue::where('router_id', $router->id)
                ->where('name', $q['name'])
                ->first();
            
            $data = [
                'router_id' => $router->id,
                'name' => $q['name'],
                'target' => $q['target'] ?? $q['target-addresses'] ?? '',
                'max_limit' => $q['max-limit'] ?? '0/0',
                'type' => 'simple',
                'comment' => $q['comment'] ?? null,
                'is_disabled' => isset($q['disabled']) && $q['disabled'] == 'true',
                'mikrotik_id' => $q['.id'] ?? null,
            ];

            if ($queue) {
                $queue->update($data);
            } else {
                Queue::create($data);
            }
            $count++;
        }
        return $count;
    }
}
