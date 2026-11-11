<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\HotspotVoucher;
use App\Models\Package;
use App\Models\PublicVoucherOrder;
use App\Models\Router;
use App\Models\Setting;
use App\Services\PaymentGatewayService;
use App\Services\PublicVoucherService;
use Illuminate\Http\Request;

class PublicVoucherController extends Controller
{
    public function __construct(
        protected PaymentGatewayService $gatewayService,
        protected PublicVoucherService $publicVoucherService,
    ) {
    }

    /**
     * Halaman publik pembelian voucher langsung (Guest Checkout).
     */
    public function index(Request $request)
    {
        $selectedRouterId = $request->integer('router_id') ?: null;

        $packages = Package::where('type', 'hotspot')
            ->where('is_active', true)
            ->where('price', '>', 0)
            ->withCount(['hotspotVouchers as available_stock' => function ($q) use ($selectedRouterId) {
                $q->where('is_used', false)
                  ->whereNull('used_at')
                  ->where(function ($sq) {
                      $sq->whereNull('expires_at')->orWhere('expires_at', '>', now());
                  });
                if ($selectedRouterId) {
                    $q->where('router_id', $selectedRouterId);
                }
            }])
            ->orderBy('price')
            ->get();

        $routers         = Router::where('is_active', true)->orderBy('name')->get(['id', 'name', 'ssid']);
        $isGatewayActive = $this->gatewayService->isGatewayActive();
        $gatewayConfig   = $this->gatewayService->getConfig();
        $brandName       = Setting::get('hotspot_brand_name', config('app.name', 'MANTIK WiFi'));

        return view('public_voucher.index', compact(
            'packages',
            'routers',
            'selectedRouterId',
            'isGatewayActive',
            'gatewayConfig',
            'brandName'
        ));
    }

    /**
     * Proses pembuatan order publik & invoice Payment Gateway.
     */
    public function checkout(Request $request)
    {
        if (!$this->gatewayService->isGatewayActive()) {
            return back()->with('error', 'Pembayaran otomatis (Payment Gateway) belum diaktifkan oleh pengelola WiFi.');
        }

        $validated = $request->validate([
            'package_id'  => ['required', 'exists:packages,id'],
            'router_id'   => ['nullable', 'exists:routers,id'],
            'buyer_name'  => ['nullable', 'string', 'max:100'],
            'buyer_phone' => ['nullable', 'string', 'min:9', 'max:20'],
        ], [
            'package_id.required' => 'Silakan pilih paket voucher terlebih dahulu.',
            'buyer_phone.min'     => 'Nomor WhatsApp minimal 9 digit.',
        ]);

        $package = Package::where('type', 'hotspot')
            ->where('is_active', true)
            ->where('price', '>', 0)
            ->find($validated['package_id']);

        if (!$package) {
            return back()->with('error', 'Paket voucher tidak ditemukan atau sedang nonaktif.');
        }

        $stockQuery = HotspotVoucher::where('package_id', $package->id)
            ->where('is_used', false)
            ->whereNull('used_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });

        if (!empty($validated['router_id'])) {
            $stockQuery->where('router_id', $validated['router_id']);
        }

        if (!$stockQuery->exists()) {
            return back()->with('error', "Maaf, stok voucher untuk paket {$package->name} saat ini sedang habis.");
        }

        $buyerPhone = !empty($validated['buyer_phone'])
            ? Customer::normalizePhoneNumber($validated['buyer_phone'])
            : null;

        $buyerName = trim((string) ($validated['buyer_name'] ?? ''));
        if ($buyerName === '') {
            $buyerName = 'Tamu Hotspot';
        }

        $gatewayConfig = $this->gatewayService->getConfig();

        $order = PublicVoucherOrder::create([
            'package_id'   => $package->id,
            'router_id'    => $validated['router_id'] ?: null,
            'buyer_name'   => $buyerName,
            'buyer_phone'  => $buyerPhone ?: null,
            'amount'       => (float) $package->price,
            'fee'          => 0,
            'total_amount' => (float) $package->price,
            'gateway'      => $gatewayConfig['provider'],
            'status'       => 'pending',
            'expired_at'   => now()->addHours(24),
            'ip_address'   => $request->ip(),
        ]);

        $order = $this->gatewayService->createPublicVoucherInvoice($order);

        if (empty($order->payment_url) && empty($order->qr_url)) {
            $order->update(['status' => 'failed']);
            return back()->with('error', 'Gagal membuat tagihan ke Payment Gateway. Silakan coba beberapa saat lagi atau hubungi admin.');
        }

        return redirect()->route('public.voucher.status', $order->reference_code);
    }

    /**
     * Halaman status pembayaran & tampilan kode voucher setelah lunas.
     */
    public function status(string $referenceCode)
    {
        $order = PublicVoucherOrder::with(['package', 'router', 'voucher.router'])
            ->where('reference_code', $referenceCode)
            ->firstOrFail();

        $brandName = Setting::get('hotspot_brand_name', config('app.name', 'MANTIK WiFi'));

        return view('public_voucher.status', compact('order', 'brandName'));
    }

    /**
     * Endpoint JSON ringan untuk polling status order dari browser tamu.
     */
    public function poll(string $referenceCode)
    {
        $order = PublicVoucherOrder::with(['voucher.router', 'router'])
            ->where('reference_code', $referenceCode)
            ->firstOrFail();

        $voucher = $order->voucher;
        $router  = $voucher?->router ?? $order->router;

        return response()->json([
            'status'       => $order->status,
            'voucher_code' => $order->status === 'paid' ? $voucher?->code : null,
            'username'     => $order->status === 'paid' ? $voucher?->username : null,
            'password'     => $order->status === 'paid' ? $voucher?->password : null,
            'ssid'         => $router?->ssid ?: $router?->name,
            'expires_at'   => $voucher?->expires_at?->format('d/m/Y H:i'),
        ]);
    }

    /**
     * Daftar riwayat pembelian voucher publik di Panel Admin.
     */
    public function adminIndex(Request $request)
    {
        $query = PublicVoucherOrder::with(['package', 'router', 'voucher']);

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('reference_code', 'like', "%{$s}%")
                  ->orWhere('buyer_name', 'like', "%{$s}%")
                  ->orWhere('buyer_phone', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'paid_count'         => PublicVoucherOrder::where('status', 'paid')->count(),
            'pending_count'      => PublicVoucherOrder::where('status', 'pending')->count(),
            'out_of_stock_count' => PublicVoucherOrder::where('status', 'paid_out_of_stock')->count(),
            'total_revenue'      => (float) PublicVoucherOrder::where('status', 'paid')->sum('amount'),
        ];

        return view('public_voucher.admin_index', compact('orders', 'stats'));
    }

    /**
     * Alokasikan ulang stok voucher untuk order yang lunas namun sebelumnya kehabisan stok.
     */
    public function retryFulfill(PublicVoucherOrder $order)
    {
        if ($order->status !== 'paid_out_of_stock') {
            return back()->with('error', 'Hanya order berstatus lunas namun stok habis yang dapat dialokasikan ulang.');
        }

        $result = $this->publicVoucherService->fulfillOrder($order);

        if ($result->status === 'paid') {
            return back()->with('success', "Voucher {$result->voucher?->code} berhasil dialokasikan untuk order {$result->reference_code}.");
        }

        return back()->with('error', 'Stok voucher untuk paket ini masih kosong. Silakan generate stok voucher terlebih dahulu.');
    }
}

