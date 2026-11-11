<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;

class PaymentGatewayWebhookController extends Controller
{
    public function __construct(protected PaymentGatewayService $gatewayService)
    {
    }

    /**
     * Endpoint callback/webhook publik untuk Payment Gateway
     * (Tripay, Midtrans, Duitku, Xendit).
     */
    public function handle(Request $request, string $provider)
    {
        $provider = strtolower(trim($provider));
        $result   = $this->gatewayService->handleCallback($provider, $request);

        return response()->json([
            'success' => $result['ok'],
            'message' => $result['message'],
        ], $result['code']);
    }
}

