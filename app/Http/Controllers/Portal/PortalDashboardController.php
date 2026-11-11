<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Billing;
use App\Models\Customer;
use App\Models\CustomerBalanceTransaction;
use App\Models\HotspotVoucher;
use App\Models\Package;
use App\Models\Router;
use App\Models\Setting;
use App\Models\TopupTransaction;
use App\Models\WaVoucherRequest;
use App\Services\BillingService;
use App\Services\PaymentGatewayService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class PortalDashboardController extends Controller
{
    public function __construct(
        protected WhatsAppService $wa,
        protected BillingService $billingService,
        protected PaymentGatewayService $paymentGateway,
    ) {
    }

    /**
     * Halaman utama Portal Pelanggan.
     */
    public function index()
    {
        $customer = auth()->user()->customer;
        $customer->load([
            'pppoeUsers.package',
            'pppoeUsers.router',
            'hotspotUsers.package',
        ]);

        $balanceTransactions = $customer->balanceTransactions()->limit(15)->get();
        $topupTransactions   = $customer->topupTransactions()->limit(10)->get();
        $voucherHistory      = $customer->waVoucherRequests()
            ->with(['package', 'voucher.router'])
            ->where('status', 'success')
            ->limit(15)
            ->get();

        $billings = $customer->billings()
            ->with('package')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $availablePackages = Package::where('type', 'hotspot')
            ->where('is_active', true)
            ->where('price', '>', 0)
            ->withCount(['hotspotVouchers as available_stock' => function ($q) {
                $q->where('is_used', false)
                  ->whereNull('used_at')
                  ->where(function ($sq) {
                      $sq->whereNull('expires_at')->orWhere('expires_at', '>', now());
                  });
            }])
            ->orderBy('price')
            ->get();

        $routers = Router::where('is_active', true)->orderBy('name')->get(['id', 'name', 'ssid']);
        $isGatewayActive = $this->paymentGateway->isGatewayActive();
        $gatewayConfig   = $this->paymentGateway->getConfig();

        return view('portal.dashboard', compact(
            'customer',
            'balanceTransactions',
            'topupTransactions',
            'voucherHistory',
            'billings',
            'availablePackages',
            'routers',
            'isGatewayActive',
            'gatewayConfig'
        ));
    }

    /**
     * Beli voucher hotspot langsung dari Portal Web menggunakan saldo deposit.
     */
    public function buyVoucher(Request $request)
    {
        $validated = $request->validate([
            'package_id' => ['required', 'exists:packages,id'],
            'router_id'  => ['nullable', 'exists:routers,id'],
        ]);

        $customer = auth()->user()->customer;
        $package  = Package::where('type', 'hotspot')
            ->where('is_active', true)
            ->where('price', '>', 0)
            ->find($validated['package_id']);

        if (!$package) {
            return back()->with('error', 'Paket hotspot tidak tersedia atau tidak aktif.');
        }

        $price = (float) $package->price;

        DB::beginTransaction();
        try {
            $lockedCustomer = Customer::where('id', $customer->id)->lockForUpdate()->firstOrFail();

            if ((float) $lockedCustomer->balance < $price) {
                DB::rollBack();
                return back()->with('error', 'Saldo deposit Anda tidak mencukupi. Silakan lakukan top-up saldo terlebih dahulu.');
            }

            $voucherQuery = HotspotVoucher::with('router')
                ->where('package_id', $package->id)
                ->where('is_used', false)
                ->whereNull('used_at')
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                });

            if (!empty($validated['router_id'])) {
                $voucherQuery->where('router_id', $validated['router_id']);
            }

            $voucher = $voucherQuery->lockForUpdate()->first();

            if (!$voucher) {
                DB::rollBack();
                return back()->with('error', 'Maaf, stok voucher untuk paket & lokasi yang dipilih sedang habis.');
            }

            // 1. Potong saldo
            $balanceBefore = (float) $lockedCustomer->balance;
            $balanceAfter  = $balanceBefore - $price;
            $lockedCustomer->update(['balance' => $balanceAfter]);

            // 2. Tandai voucher terpakai
            $expiresAt = now()->addDays($package->duration_days);
            $voucher->update([
                'is_used'    => true,
                'used_by'    => $lockedCustomer->name . ' (Portal)',
                'used_at'    => now(),
                'expires_at' => $expiresAt,
            ]);

            // 3. Catat ke riwayat voucher
            $waReq = WaVoucherRequest::create([
                'phone'          => $lockedCustomer->phone ?: '-',
                'customer_id'    => $lockedCustomer->id,
                'requester_name' => $lockedCustomer->name,
                'package_code'   => strtoupper($package->slug),
                'package_id'     => $package->id,
                'voucher_id'     => $voucher->id,
                'voucher_code'   => $voucher->code,
                'price_deducted' => $price,
                'status'         => 'success',
                'raw_message'    => 'PORTAL_WEB_BUY',
            ]);

            // 4. Catat ke buku mutasi saldo
            CustomerBalanceTransaction::create([
                'customer_id'    => $lockedCustomer->id,
                'type'           => 'debit',
                'category'       => 'voucher_portal',
                'amount'         => $price,
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceAfter,
                'reference_type' => WaVoucherRequest::class,
                'reference_id'   => $waReq->id,
                'notes'          => "Beli voucher {$package->name} ({$voucher->code}) via Portal Pelanggan",
                'created_by'     => auth()->id(),
            ]);

            DB::commit();

            // Kirim salinan kode voucher ke WhatsApp pelanggan jika tersedia
            if (!empty($lockedCustomer->phone)) {
                try {
                    $router = $voucher->router;
                    $ssid = $router?->ssid ?: $router?->name;
                    $durationLabel = $package->duration_days >= 1
                        ? $package->duration_days . ' Hari'
                        : ($package->duration_days * 24) . ' Jam';

                    $this->wa->sendVoucher($lockedCustomer->phone, [
                        'name'         => $lockedCustomer->name,
                        'code'         => $voucher->code,
                        'package_name' => $package->name,
                        'speed'        => "{$package->bandwidth_up}/{$package->bandwidth_down} Mbps",
                        'duration'     => $durationLabel,
                        'expires_at'   => $expiresAt->toDateTimeString(),
                        'price'        => $price,
                        'router_name'  => $router?->name,
                        'ssid'         => $ssid,
                        'brand_name'   => Setting::get('hotspot_brand_name', config('app.name', 'MANTIK Hotspot')),
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('[Portal Buy Voucher] Gagal kirim salinan WA: ' . $e->getMessage());
                }
            }

            return redirect()
                ->route('portal.dashboard')
                ->with('success', "Pembelian voucher {$package->name} berhasil!")
                ->with('purchased_voucher', [
                    'code'         => $voucher->code,
                    'package_name' => $package->name,
                    'ssid'         => $voucher->router?->ssid ?: $voucher->router?->name,
                    'expires_at'   => $expiresAt->format('d/m/Y H:i'),
                ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[Portal Buy Voucher Error] ' . $e->getMessage());
            return back()->with('error', 'Terjadi kendala sistem saat memproses pembelian voucher.');
        }
    }

    /**
     * Ajukan request top-up saldo (manual atau otomatis melalui Payment Gateway bila aktif).
     */
    public function requestTopup(Request $request)
    {
        $validated = $request->validate([
            'amount'         => ['required', 'numeric', 'min:5000', 'max:50000000'],
            'payment_method' => ['required', Rule::in(['bank_transfer', 'admin_cash', 'payment_gateway'])],
            'notes'          => ['nullable', 'string', 'max:255'],
        ], [
            'amount.min' => 'Minimal pengajuan top-up adalah Rp 5.000.',
        ]);

        if ($validated['payment_method'] === 'payment_gateway' && !$this->paymentGateway->isGatewayActive()) {
            return back()->with('error', 'Payment Gateway otomatis belum diaktifkan oleh admin.');
        }

        $customer = auth()->user()->customer;

        $topup = TopupTransaction::create([
            'customer_id'    => $customer->id,
            'amount'         => (float) $validated['amount'],
            'fee'            => 0,
            'total_amount'   => (float) $validated['amount'],
            'gateway'        => 'manual',
            'payment_method' => $validated['payment_method'],
            'status'         => 'pending',
            'expired_at'     => now()->addHours(24),
            'notes'          => $validated['notes'] ?? 'Pengajuan top-up melalui Portal Pelanggan',
            'created_by'     => auth()->id(),
        ]);

        if ($validated['payment_method'] === 'payment_gateway') {
            $topup = $this->paymentGateway->createTopupInvoice($topup);

            if (!empty($topup->payment_url)) {
                return redirect()
                    ->route('portal.dashboard')
                    ->with('success', "Invoice top-up #{$topup->reference_code} sebesar Rp " . number_format((float) $topup->amount, 0, ',', '.') . ' berhasil dibuat. Klik tombol Bayar Sekarang di riwayat top-up untuk menyelesaikan pembayaran otomatis.')
                    ->with('payment_url', $topup->payment_url);
            }
        }

        // Notifikasi admin via WA jika mode manual / fallback
        $adminPhone = Setting::get('whatsapp_admin_phone') ?: config('whatsapp.admin_phone');
        if ($adminPhone) {
            try {
                $amountFmt = number_format((float) $topup->amount, 0, ',', '.');
                $this->wa->sendMessage(
                    $adminPhone,
                    "💳 *Pengajuan Top-Up Pelanggan*\n"
                    . "Nama: *{$customer->name}*\n"
                    . "No. WA: {$customer->phone}\n"
                    . "Nominal: *Rp {$amountFmt}*\n"
                    . "Kode Ref: `{$topup->reference_code}`\n"
                    . "Metode: {$topup->payment_method_label}"
                );
            } catch (\Throwable $e) {
                Log::warning('[Portal Topup] Gagal kirim notif admin: ' . $e->getMessage());
            }
        }

        return redirect()
            ->route('portal.dashboard')
            ->with('success', "Pengajuan top-up #{$topup->reference_code} sebesar Rp " . number_format((float) $topup->amount, 0, ',', '.') . ' berhasil dibuat. Silakan selesaikan pembayaran atau hubungi admin untuk konfirmasi.');
    }

    /**
     * Bayar tagihan bulanan pelanggan langsung menggunakan saldo deposit.
     */
    public function payBillingWithBalance(Billing $billing)
    {
        $customer = auth()->user()->customer;

        if ((int) $billing->customer_id !== (int) $customer->id) {
            abort(403, 'Tagihan ini bukan milik akun Anda.');
        }

        try {
            $updated = $this->billingService->payInvoice(
                $billing,
                $billing->remaining_amount,
                'balance',
                auth()->id(),
                'Dibayar oleh pelanggan melalui Portal Web'
            );

            return redirect()
                ->route('portal.dashboard')
                ->with('success', "Tagihan {$updated->invoice_number} sebesar Rp " . number_format((float) $updated->paid_amount, 0, ',', '.') . ' berhasil dilunasi dengan saldo deposit!');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('[Portal Pay Billing] ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat memproses pembayaran tagihan.');
        }
    }

    /**
     * Perbarui password login portal pelanggan.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'current_password.required' => 'Password saat ini harus diisi.',
            'password.min'              => 'Password baru minimal 6 karakter.',
            'password.confirmed'        => 'Konfirmasi password baru tidak cocok.',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini tidak sesuai.']);
        }

        $user->update([
            'password' => Hash::make($request->input('password')),
        ]);

        return redirect()
            ->route('portal.dashboard')
            ->with('success', 'Password akun portal Anda berhasil diperbarui.');
    }
}

