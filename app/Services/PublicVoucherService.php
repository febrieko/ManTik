<?php

namespace App\Services;

use App\Models\HotspotVoucher;
use App\Models\PublicVoucherOrder;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PublicVoucherService
{
    public function __construct(protected WhatsAppService $wa)
    {
    }

    /**
     * Penuhi order voucher publik secara atomik setelah pembayaran terkonfirmasi PAID.
     * Mengambil 1 stok voucher dengan row lock (idempoten).
     */
    public function fulfillOrder(PublicVoucherOrder $order, ?array $gatewayPayload = null): PublicVoucherOrder
    {
        $fulfilled = DB::transaction(function () use ($order, $gatewayPayload) {
            /** @var PublicVoucherOrder $locked */
            $locked = PublicVoucherOrder::with(['package', 'router'])
                ->lockForUpdate()
                ->findOrFail($order->id);

            if ($locked->status === 'paid' && !is_null($locked->voucher_id)) {
                return $locked->fresh(['package', 'router', 'voucher.router']);
            }

            $voucherQuery = HotspotVoucher::with('router')
                ->where('package_id', $locked->package_id)
                ->where('is_used', false)
                ->whereNull('used_at')
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                });

            if (!is_null($locked->router_id)) {
                $voucherQuery->where('router_id', $locked->router_id);
            }

            /** @var HotspotVoucher|null $voucher */
            $voucher = $voucherQuery->lockForUpdate()->first();

            if (!$voucher) {
                $locked->update([
                    'status'  => 'paid_out_of_stock',
                    'paid_at' => $locked->paid_at ?: now(),
                    'payload' => $gatewayPayload ?? $locked->payload,
                ]);

                return $locked->fresh(['package', 'router']);
            }

            $durationDays = max(1, (int) ($locked->package?->duration_days ?? 1));
            $expiresAt    = now()->addDays($durationDays);

            $voucher->update([
                'is_used'    => true,
                'used_by'    => trim($locked->buyer_name ?: 'Tamu Hotspot') . ' (Direct PG)',
                'used_at'    => now(),
                'expires_at' => $expiresAt,
            ]);

            $locked->update([
                'status'     => 'paid',
                'voucher_id' => $voucher->id,
                'paid_at'    => $locked->paid_at ?: now(),
                'payload'    => $gatewayPayload ?? $locked->payload,
            ]);

            return $locked->fresh(['package', 'router', 'voucher.router']);
        });

        $this->notifyAfterFulfill($fulfilled);

        return $fulfilled;
    }

    /**
     * Kirim kode voucher ke WA pembeli (jika diisi) & kirim info ke WA admin.
     */
    protected function notifyAfterFulfill(PublicVoucherOrder $order): void
    {
        $adminPhone = Setting::get('whatsapp_admin_phone') ?: config('whatsapp.admin_phone');

        if ($order->status === 'paid_out_of_stock') {
            if ($adminPhone) {
                try {
                    $amountFmt = number_format((float) $order->amount, 0, ',', '.');
                    $this->wa->sendMessage(
                        $adminPhone,
                        "⚠️ *ORDER VOUCHER PUBLIK LUNAS TAPI STOK HABIS*\n"
                        . "Ref: `{$order->reference_code}`\n"
                        . "Pembeli: {$order->buyer_name}" . ($order->buyer_phone ? " ({$order->buyer_phone})" : '') . "\n"
                        . "Paket: " . ($order->package?->name ?? '-') . "\n"
                        . "Nominal: Rp {$amountFmt}\n"
                        . "Mohon segera kirimkan voucher secara manual."
                    );
                } catch (\Throwable $e) {
                    Log::warning('[PublicVoucherService] Gagal kirim alert stok habis ke admin: ' . $e->getMessage());
                }
            }
            return;
        }

        if ($order->status !== 'paid' || !$order->voucher) {
            return;
        }

        $voucher = $order->voucher;
        $package = $order->package;
        $router  = $voucher->router ?? $order->router;
        $ssid    = $router?->ssid ?: $router?->name;

        if (!empty($order->buyer_phone) && $package) {
            try {
                $durationLabel = $package->duration_days >= 1
                    ? $package->duration_days . ' Hari'
                    : ($package->duration_days * 24) . ' Jam';

                $this->wa->sendVoucher($order->buyer_phone, [
                    'name'         => $order->buyer_name,
                    'code'         => $voucher->code,
                    'package_name' => $package->name,
                    'speed'        => "{$package->bandwidth_up}/{$package->bandwidth_down} Mbps",
                    'duration'     => $durationLabel,
                    'expires_at'   => $voucher->expires_at?->toDateTimeString() ?? '-',
                    'price'        => (float) $order->amount,
                    'router_name'  => $router?->name,
                    'ssid'         => $ssid,
                    'brand_name'   => Setting::get('hotspot_brand_name', config('app.name', 'MANTIK Hotspot')),
                ]);
            } catch (\Throwable $e) {
                Log::warning('[PublicVoucherService] Gagal kirim WA ke pembeli: ' . $e->getMessage());
            }
        }

        if ($adminPhone) {
            try {
                $amountFmt = number_format((float) $order->amount, 0, ',', '.');
                $this->wa->sendMessage(
                    $adminPhone,
                    "🛒 *Penjualan Voucher Publik (PG)*\n"
                    . "Ref: `{$order->reference_code}`\n"
                    . "Pembeli: *{$order->buyer_name}*" . ($order->buyer_phone ? " ({$order->buyer_phone})" : '') . "\n"
                    . "Paket: " . ($package?->name ?? '-') . "\n"
                    . "Kode: `{$voucher->code}`\n"
                    . "Nominal: *Rp {$amountFmt}* via " . strtoupper($order->gateway)
                );
            } catch (\Throwable $e) {
                Log::warning('[PublicVoucherService] Gagal kirim notif penjualan admin: ' . $e->getMessage());
            }
        }
    }
}

