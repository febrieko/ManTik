<?php

namespace App\Http\Controllers;

use App\Models\WaVoucherRequest;
use App\Models\Package;
use App\Models\HotspotVoucher;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppVoucherController extends Controller
{
    protected WhatsAppService $wa;

    public function __construct(WhatsAppService $wa)
    {
        $this->wa = $wa;
    }

    /**
     * Halaman admin: Riwayat request voucher via WhatsApp.
     */
    public function index(Request $request)
    {
        $query = WaVoucherRequest::with(['package', 'voucher'])
            ->orderByDesc('created_at');

        // Filter status
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // Filter pencarian nomor / nama
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('phone', 'like', "%{$search}%")
                  ->orWhere('requester_name', 'like', "%{$search}%")
                  ->orWhere('voucher_code', 'like', "%{$search}%");
            });
        }

        // Filter berdasarkan paket
        if ($request->filled('package_id')) {
            $query->where('package_id', $request->get('package_id'));
        }

        // Filter rentang tanggal
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        $requests = $query->paginate(20)->withQueryString();
        $packages  = Package::where('type', 'hotspot')->where('is_active', true)->get();

        // Statistik ringkas
        $stats = [
            'total'   => WaVoucherRequest::count(),
            'success' => WaVoucherRequest::where('status', 'success')->count(),
            'failed'  => WaVoucherRequest::where('status', 'failed')->count(),
            'today'   => WaVoucherRequest::whereDate('created_at', today())->count(),
        ];

        return view('whatsapp.voucher-requests', compact('requests', 'packages', 'stats'));
    }

    /**
     * Halaman pengaturan WhatsApp Gateway.
     */
    public function settings()
    {
        $settings = [
            'api_url'        => config('whatsapp.api_url'),
            'api_key_set'    => !empty(config('whatsapp.api_key')),
            'webhook_secret' => config('whatsapp.webhook_secret'),
            'admin_phone'    => config('whatsapp.admin_phone'),
        ];

        $availablePackages = Package::where('type', 'hotspot')
            ->where('is_active', true)
            ->get();

        $stockSummary = HotspotVoucher::selectRaw('package_id, COUNT(*) as total, SUM(is_used = 0) as available')
            ->with('package:id,name,slug')
            ->groupBy('package_id')
            ->get();

        return view('whatsapp.settings', compact('settings', 'availablePackages', 'stockSummary'));
    }

    /**
     * Update pengaturan WhatsApp Gateway di .env.
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'api_url'        => 'required|url',
            'api_key'        => 'nullable|string|max:255',
            'webhook_secret' => 'nullable|string|max:100',
            'admin_phone'    => 'nullable|string|max:20',
        ]);

        $envPath = base_path('.env');
        $content = file_get_contents($envPath);

        $replacements = [
            'WHATSAPP_API_URL'        => $validated['api_url'],
            'WHATSAPP_WEBHOOK_SECRET' => $validated['webhook_secret'] ?? '',
            'WHATSAPP_ADMIN_PHONE'    => $validated['admin_phone'] ?? '',
        ];

        // Hanya update API key jika diisi (biar tidak overwrite dengan kosong)
        if (!empty($validated['api_key'])) {
            $replacements['WHATSAPP_API_KEY'] = $validated['api_key'];
        }

        foreach ($replacements as $key => $value) {
            if (preg_match("/^{$key}=.*/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
            } else {
                $content .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $content);

        return back()->with('success', 'Pengaturan WhatsApp Gateway berhasil disimpan.');
    }

    /**
     * Test koneksi / kirim pesan test.
     */
    public function testSend(Request $request)
    {
        $validated = $request->validate([
            'phone'   => 'required|string',
            'message' => 'nullable|string|max:500',
        ]);

        $message = $validated['message'] ?? "✅ Tes koneksi WhatsApp Gateway berhasil!\n\nSistem siap menerima request voucher hotspot.";

        $result = $this->wa->sendMessage($validated['phone'], $message);

        if ($result['success']) {
            return back()->with('success', 'Pesan tes berhasil dikirim ke ' . $result['phone']);
        }

        return back()->with('error', 'Gagal kirim pesan: ' . ($result['error'] ?? 'Unknown error'));
    }

    /**
     * Kirim ulang voucher ke nomor yang sama.
     */
    public function resendVoucher(WaVoucherRequest $waRequest)
    {
        if ($waRequest->status !== 'success' || !$waRequest->voucher) {
            return back()->with('error', 'Voucher tidak dapat dikirim ulang (tidak ada data voucher).');
        }

        $voucher = $waRequest->voucher;
        $package = $waRequest->package;

        $durationLabel = $package
            ? ($package->duration_days . ' Hari')
            : '-';

        $result = $this->wa->sendVoucher($waRequest->phone, [
            'name'         => $waRequest->requester_name ?? 'Pengunjung',
            'code'         => $voucher->code,
            'package_name' => $package ? $package->name : '-',
            'speed'        => $package ? "{$package->bandwidth_up}/{$package->bandwidth_down} Mbps" : '-',
            'duration'     => $durationLabel,
            'expires_at'   => $voucher->expires_at,
        ]);

        if ($result['success']) {
            Log::info('[WA Admin] Resend voucher ke ' . $waRequest->phone . ' berhasil.', [
                'voucher'    => $voucher->code,
                'admin_user' => auth()->id(),
            ]);
            return back()->with('success', 'Voucher berhasil dikirim ulang ke ' . $waRequest->phone);
        }

        return back()->with('error', 'Gagal kirim ulang: ' . ($result['error'] ?? 'Unknown error'));
    }
}
