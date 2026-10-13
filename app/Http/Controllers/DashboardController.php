<?php

namespace App\Http\Controllers;

use App\Models\Router;
use App\Models\PppoeUser;
use App\Models\HotspotUser;
use App\Models\HotspotVoucher;
use App\Models\Billing;
use App\Models\Customer;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->isTeknisi()) {
            $routers = $user->assignedRouters()->with('latestStat')->get();
            $routerIds = $routers->pluck('id');

            $totalPppoe = PppoeUser::whereIn('router_id', $routerIds)->count();
            $activePppoe = PppoeUser::whereIn('router_id', $routerIds)->where('is_disabled', false)->count();

            $totalHotspot = HotspotUser::whereIn('router_id', $routerIds)->count();
            $vouchersAvail = HotspotVoucher::whereIn('router_id', $routerIds)->where('is_used', false)->count();

            $totalRevenue = 0;
            $unpaidBillings = 0;
            $recentBillings = collect();
        } else {
            $routers = Router::with('latestStat')->get();
            
            $totalPppoe = PppoeUser::count();
            $activePppoe = PppoeUser::where('is_disabled', false)->count();

            $totalHotspot = HotspotUser::count();
            $vouchersAvail = HotspotVoucher::where('is_used', false)->count();

            $totalRevenue = Billing::where('status', 'paid')
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->sum('amount');

            $unpaidBillings = Billing::whereIn('status', ['unpaid', 'overdue'])->count();

            $recentBillings = Billing::with('customer')
                ->orderByDesc('created_at')
                ->limit(8)
                ->get();
        }

        $totalRouters = $routers->count();
        $onlineRouters = $routers->where('status', 'online')->count();

        return view('dashboard', compact(
            'routers',
            'totalRouters',
            'onlineRouters',
            'totalPppoe',
            'activePppoe',
            'totalHotspot',
            'vouchersAvail',
            'totalRevenue',
            'unpaidBillings',
            'recentBillings',
        ));
    }
}
