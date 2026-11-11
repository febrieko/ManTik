<?php

namespace App\Services;

use App\Models\PublicVoucherOrder;
use App\Models\Setting;
use App\Models\TopupTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentGatewayService
{
    public function __construct(
        protected CustomerBalanceService $balanceService,
        protected PublicVoucherService $publicVoucherService,
    ) {
    }

    /**
     * Ambil konfigurasi Payment Gateway dari database Setting.
     */
    public function getConfig(): array
    {
        return [
            'enabled'         => (bool) Setting::get('payment_gateway_enabled', false),
            'provider'        => (string) Setting::get('payment_gateway_provider', 'manual'),
            'mode'            => (string) Setting::get('payment_gateway_mode', 'sandbox'),
            'merchant_code'   => (string) Setting::get('payment_gateway_merchant_code', ''),
            'api_key'         => (string) Setting::get('payment_gateway_api_key', ''),
            'private_key'     => (string) Setting::get('payment_gateway_private_key', ''),
            'default_channel' => (string) Setting::get('payment_gateway_default_channel', 'QRIS'),
        ];
    }

    /**
     * Cek apakah Payment Gateway aktif dan memiliki kredensial minimal.
     */
    public function isGatewayActive(): bool
    {
        $cfg = $this->getConfig();
        return $cfg['enabled']
            && $cfg['provider'] !== 'manual'
            && !empty($cfg['api_key']);
    }

    /**
     * Buat invoice pembayaran di Payment Gateway untuk transaksi top-up saldo pelanggan.
     */
    public function createTopupInvoice(TopupTransaction $topup, ?string $channel = null): TopupTransaction
    {
        if (!$this->isGatewayActive()) {
            return $topup;
        }

        $customer = $topup->customer;
        $phone    = $customer->phone ?: '08123456789';

        $result = $this->requestGatewayInvoice(
            referenceCode: $topup->reference_code,
            amount: (int) round((float) $topup->amount),
            buyer: [
                'name'  => $customer->name,
                'email' => $customer->email ?: ($phone . '@customer.local'),
                'phone' => $phone,
            ],
            item: [
                'sku'  => 'TOPUP-SALDO',
                'name' => 'Top-Up Saldo Deposit MANTIK',
            ],
            returnUrl: route('portal.dashboard'),
            channel: $channel,
        );

        if (!empty($result)) {
            $topup->update($result);
        }

        return $topup->fresh();
    }

    /**
     * Buat invoice pembayaran di Payment Gateway untuk order pembelian voucher publik (Guest Checkout).
     */
    public function createPublicVoucherInvoice(PublicVoucherOrder $order, ?string $channel = null): PublicVoucherOrder
    {
        if (!$this->isGatewayActive()) {
            return $order;
        }

        $phone       = $order->buyer_phone ?: '08123456789';
        $packageName = $order->package?->name ?: 'Voucher Hotspot MANTIK';

        $result = $this->requestGatewayInvoice(
            referenceCode: $order->reference_code,
            amount: (int) round((float) $order->amount),
            buyer: [
                'name'  => $order->buyer_name ?: 'Tamu Hotspot',
                'email' => preg_replace('/\D/', '', $phone) . '@guest.local',
                'phone' => $phone,
            ],
            item: [
                'sku'  => 'VOUCHER-' . $order->package_id,
                'name' => 'Voucher WiFi: ' . $packageName,
            ],
            returnUrl: route('public.voucher.status', $order->reference_code),
            channel: $channel,
        );

        if (!empty($result)) {
            $order->update($result);
        }

        return $order->fresh();
    }

    /**
     * Helper terpadu memanggil API Tripay, Midtrans, Duitku, atau Xendit.
     * Mengembalikan array kolom update jika sukses, atau [] jika gagal.
     */
    protected function requestGatewayInvoice(
        string $referenceCode,
        int $amount,
        array $buyer,
        array $item,
        string $returnUrl,
        ?string $channel = null
    ): array {
        $cfg         = $this->getConfig();
        $provider    = $cfg['provider'];
        $channel     = $channel ?: $cfg['default_channel'];
        $callbackUrl = url("/api/payment-gateway/callback/{$provider}");

        try {
            if ($provider === 'tripay') {
                $baseUrl = $cfg['mode'] === 'production'
                    ? 'https://tripay.co.id/api/transaction/create'
                    : 'https://tripay.co.id/api-sandbox/transaction/create';

                $signature = hash_hmac('sha256', $cfg['merchant_code'] . $referenceCode . $amount, $cfg['private_key']);

                $res = Http::timeout(15)
                    ->withToken($cfg['api_key'])
                    ->post($baseUrl, [
                        'method'         => $channel,
                        'merchant_ref'   => $referenceCode,
                        'amount'         => $amount,
                        'customer_name'  => $buyer['name'],
                        'customer_email' => $buyer['email'],
                        'customer_phone' => $buyer['phone'],
                        'order_items'    => [[
                            'sku'      => $item['sku'],
                            'name'     => $item['name'],
                            'price'    => $amount,
                            'quantity' => 1,
                        ]],
                        'callback_url'   => $callbackUrl,
                        'return_url'     => $returnUrl,
                        'expired_time'   => now()->addHours(24)->timestamp,
                        'signature'      => $signature,
                    ]);

                $data = $res->json('data') ?? [];
                if ($res->successful() && !empty($data)) {
                    return [
                        'gateway'           => 'tripay',
                        'payment_method'    => strtolower($channel),
                        'gateway_reference' => $data['reference'] ?? null,
                        'payment_url'       => $data['checkout_url'] ?? null,
                        'qr_url'            => $data['qr_url'] ?? null,
                        'fee'               => (float) ($data['total_fee'] ?? 0),
                        'total_amount'      => (float) ($data['amount'] ?? $amount),
                        'payload'           => $data,
                    ];
                }

                Log::warning('[PaymentGateway][Tripay] Gagal create invoice', ['response' => $res->json()]);
            } elseif ($provider === 'midtrans') {
                $baseUrl = $cfg['mode'] === 'production'
                    ? 'https://app.midtrans.com/snap/v1/transactions'
                    : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

                $res = Http::timeout(15)
                    ->withBasicAuth($cfg['api_key'], '')
                    ->post($baseUrl, [
                        'transaction_details' => [
                            'order_id'     => $referenceCode,
                            'gross_amount' => $amount,
                        ],
                        'customer_details' => [
                            'first_name' => $buyer['name'],
                            'email'      => $buyer['email'],
                            'phone'      => $buyer['phone'],
                        ],
                        'callbacks' => [
                            'finish' => $returnUrl,
                        ],
                    ]);

                $data = $res->json() ?? [];
                if ($res->successful() && !empty($data['redirect_url'])) {
                    return [
                        'gateway'           => 'midtrans',
                        'payment_method'    => 'snap',
                        'gateway_reference' => $data['token'] ?? null,
                        'payment_url'       => $data['redirect_url'],
                        'payload'           => $data,
                    ];
                }

                Log::warning('[PaymentGateway][Midtrans] Gagal create snap', ['response' => $data]);
            } elseif ($provider === 'duitku') {
                $baseUrl = $cfg['mode'] === 'production'
                    ? 'https://passport.duitku.com/webapi/api/merchant/v2/inquiry'
                    : 'https://sandbox.duitku.com/webapi/api/merchant/v2/inquiry';

                $signature = md5($cfg['merchant_code'] . $referenceCode . $amount . $cfg['api_key']);

                $res = Http::timeout(15)->post($baseUrl, [
                    'merchantCode'    => $cfg['merchant_code'],
                    'paymentAmount'   => $amount,
                    'paymentMethod'   => $channel,
                    'merchantOrderId' => $referenceCode,
                    'productDetails'  => $item['name'],
                    'customerVaName'  => $buyer['name'],
                    'email'           => $buyer['email'],
                    'phoneNumber'     => $buyer['phone'],
                    'callbackUrl'     => $callbackUrl,
                    'returnUrl'       => $returnUrl,
                    'signature'       => $signature,
                    'expiryPeriod'    => 1440,
                ]);

                $data = $res->json() ?? [];
                if ($res->successful() && !empty($data['paymentUrl'])) {
                    return [
                        'gateway'           => 'duitku',
                        'payment_method'    => strtolower($channel),
                        'gateway_reference' => $data['reference'] ?? null,
                        'payment_url'       => $data['paymentUrl'],
                        'qr_url'            => $data['qrString'] ?? null,
                        'payload'           => $data,
                    ];
                }

                Log::warning('[PaymentGateway][Duitku] Gagal create inquiry', ['response' => $data]);
            } elseif ($provider === 'xendit') {
                $res = Http::timeout(15)
                    ->withBasicAuth($cfg['api_key'], '')
                    ->post('https://api.xendit.co/v2/invoices', [
                        'external_id'          => $referenceCode,
                        'amount'               => $amount,
                        'payer_email'          => $buyer['email'],
                        'description'          => $item['name'],
                        'success_redirect_url' => $returnUrl,
                        'failure_redirect_url' => $returnUrl,
                        'invoice_duration'     => 86400,
                    ]);

                $data = $res->json() ?? [];
                if ($res->successful() && !empty($data['invoice_url'])) {
                    return [
                        'gateway'           => 'xendit',
                        'payment_method'    => 'xendit_invoice',
                        'gateway_reference' => $data['id'] ?? null,
                        'payment_url'       => $data['invoice_url'],
                        'payload'           => $data,
                    ];
                }

                Log::warning('[PaymentGateway][Xendit] Gagal create invoice', ['response' => $data]);
            }
        } catch (\Throwable $e) {
            Log::error("[PaymentGateway][{$provider}] Exception: " . $e->getMessage());
        }

        return [];
    }

    /**
     * Verifikasi signature callback dari masing-masing Payment Gateway.
     */
    public function verifyCallbackSignature(string $provider, Request $request): bool
    {
        $cfg = $this->getConfig();

        return match ($provider) {
            'tripay' => !empty($cfg['private_key']) && hash_equals(
                hash_hmac('sha256', $request->getContent(), $cfg['private_key']),
                (string) $request->header('X-Callback-Signature')
            ),
            'midtrans' => !empty($cfg['api_key']) && hash_equals(
                hash('sha512', $request->input('order_id') . $request->input('status_code') . $request->input('gross_amount') . $cfg['api_key']),
                (string) $request->input('signature_key')
            ),
            'duitku' => !empty($cfg['api_key']) && hash_equals(
                md5($request->input('merchantCode') . $request->input('amount') . $request->input('merchantOrderId') . $cfg['api_key']),
                (string) $request->input('signature')
            ),
            'xendit' => !empty($cfg['private_key']) && hash_equals(
                $cfg['private_key'],
                (string) $request->header('x-callback-token')
            ),
            default => false,
        };
    }

    /**
     * Proses callback/webhook dari Payment Gateway (baik untuk Topup Saldo maupun Order Voucher Publik).
     */
    public function handleCallback(string $provider, Request $request): array
    {
        if (!$this->verifyCallbackSignature($provider, $request)) {
            Log::warning("[PaymentGateway][{$provider}] Invalid callback signature");
            return ['ok' => false, 'code' => 401, 'message' => 'Invalid signature'];
        }

        $referenceCode = match ($provider) {
            'tripay'   => $request->input('merchant_ref'),
            'midtrans' => $request->input('order_id'),
            'duitku'   => $request->input('merchantOrderId'),
            'xendit'   => $request->input('external_id'),
            default    => null,
        };

        if (!$referenceCode) {
            return ['ok' => false, 'code' => 400, 'message' => 'Missing reference code'];
        }

        $isPaid = match ($provider) {
            'tripay'   => strtoupper((string) $request->input('status')) === 'PAID',
            'midtrans' => in_array($request->input('transaction_status'), ['settlement', 'capture'])
                          && ($request->input('fraud_status', 'accept') === 'accept'),
            'duitku'   => $request->input('resultCode') === '00',
            'xendit'   => strtoupper((string) $request->input('status')) === 'PAID',
            default    => false,
        };

        $isFailedOrExpired = match ($provider) {
            'tripay'   => in_array(strtoupper((string) $request->input('status')), ['EXPIRED', 'FAILED']),
            'midtrans' => in_array($request->input('transaction_status'), ['expire', 'cancel', 'deny']),
            'duitku'   => $request->input('resultCode') !== '00',
            'xendit'   => in_array(strtoupper((string) $request->input('status')), ['EXPIRED', 'FAILED']),
            default    => false,
        };

        // 1. Cek apakah callback untuk Top-Up Saldo Pelanggan (TOPUP-...)
        $topup = TopupTransaction::where('reference_code', $referenceCode)->first();
        if ($topup) {
            if ($topup->status === 'paid') {
                return ['ok' => true, 'code' => 200, 'message' => 'Already paid'];
            }

            if ($isPaid) {
                $this->balanceService->approveTopup($topup, null, $request->all());
                return ['ok' => true, 'code' => 200, 'message' => 'Topup credited'];
            }

            if ($isFailedOrExpired) {
                $newStatus = $this->resolveFailedStatus($request);
                $topup->update(['status' => $newStatus, 'payload' => $request->all()]);
                return ['ok' => true, 'code' => 200, 'message' => "Marked as {$newStatus}"];
            }

            return ['ok' => true, 'code' => 200, 'message' => 'Callback ignored (non-final status)'];
        }

        // 2. Cek apakah callback untuk Pembelian Voucher Publik (VCR-...)
        $order = PublicVoucherOrder::where('reference_code', $referenceCode)->first();
        if ($order) {
            if ($order->status === 'paid' && !is_null($order->voucher_id)) {
                return ['ok' => true, 'code' => 200, 'message' => 'Already paid'];
            }

            if ($isPaid) {
                $fulfilled = $this->publicVoucherService->fulfillOrder($order, $request->all());
                return [
                    'ok'      => true,
                    'code'    => 200,
                    'message' => $fulfilled->status === 'paid' ? 'Public voucher fulfilled' : 'Paid but voucher out of stock',
                ];
            }

            if ($isFailedOrExpired) {
                $newStatus = $this->resolveFailedStatus($request);
                $order->update(['status' => $newStatus, 'payload' => $request->all()]);
                return ['ok' => true, 'code' => 200, 'message' => "Order marked as {$newStatus}"];
            }

            return ['ok' => true, 'code' => 200, 'message' => 'Callback ignored (non-final status)'];
        }

        return ['ok' => false, 'code' => 404, 'message' => 'Transaction not found'];
    }

    protected function resolveFailedStatus(Request $request): string
    {
        $raw = strtoupper((string) ($request->input('status') ?: $request->input('transaction_status')));
        return in_array($raw, ['EXPIRED', 'EXPIRE']) ? 'expired' : 'failed';
    }
}
