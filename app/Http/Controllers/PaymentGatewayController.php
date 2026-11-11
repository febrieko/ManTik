<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;

class PaymentGatewayController extends Controller
{
    public function __construct(protected PaymentGatewayService $gatewayService)
    {
    }

    /**
     * Halaman pengaturan integrasi Payment Gateway.
     */
    public function settings()
    {
        $config   = $this->gatewayService->getConfig();
        $isActive = $this->gatewayService->isGatewayActive();

        $callbackUrls = [
            'tripay'   => url('/api/payment-gateway/callback/tripay'),
            'midtrans' => url('/api/payment-gateway/callback/midtrans'),
            'duitku'   => url('/api/payment-gateway/callback/duitku'),
            'xendit'   => url('/api/payment-gateway/callback/xendit'),
        ];

        return view('payment_gateway.settings', compact('config', 'isActive', 'callbackUrls'));
    }

    /**
     * Simpan konfigurasi Payment Gateway ke tabel settings.
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'enabled'         => 'nullable|boolean',
            'provider'        => 'required|in:manual,tripay,midtrans,duitku,xendit',
            'mode'            => 'required|in:sandbox,production',
            'merchant_code'   => 'nullable|string|max:190',
            'api_key'         => 'nullable|string|max:255',
            'private_key'     => 'nullable|string|max:255',
            'default_channel' => 'nullable|string|max:60',
        ]);

        $enabled = $request->boolean('enabled') && $validated['provider'] !== 'manual';

        Setting::set('payment_gateway_enabled', $enabled, 'payment_gateway');
        Setting::set('payment_gateway_provider', $validated['provider'], 'payment_gateway');
        Setting::set('payment_gateway_mode', $validated['mode'], 'payment_gateway');
        Setting::set('payment_gateway_merchant_code', trim((string) ($validated['merchant_code'] ?? '')), 'payment_gateway');
        Setting::set('payment_gateway_api_key', trim((string) ($validated['api_key'] ?? '')), 'payment_gateway');
        Setting::set('payment_gateway_private_key', trim((string) ($validated['private_key'] ?? '')), 'payment_gateway');
        Setting::set('payment_gateway_default_channel', strtoupper(trim((string) ($validated['default_channel'] ?: 'QRIS'))), 'payment_gateway');

        return redirect()->route('payment-gateway.settings')
            ->with('success', 'Konfigurasi Payment Gateway berhasil disimpan.');
    }
}

