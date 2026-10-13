<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HotspotVoucher;
use App\Models\Router;
use Illuminate\Http\Request;

class HotspotWebhookController extends Controller
{
    public function onLogin(Request $request)
    {
        $username = $request->get('user');
        $mac = $request->get('mac');
        
        if (!$username) {
            return response()->json(['success' => false, 'message' => 'Missing user parameter']);
        }

        // Find the voucher in database
        $voucher = HotspotVoucher::where('username', $username)->first();
        
        if ($voucher) {
            // If it's the first time logging in
            if (!$voucher->is_used) {
                // Set as used and set expiration based on package duration
                $durationDays = $voucher->package ? $voucher->package->duration_days : 1; // Default 1 day
                
                $voucher->update([
                    'is_used' => true,
                    'used_by' => $mac,
                    'used_at' => now(),
                    'expires_at' => now()->addDays($durationDays)
                ]);

                // The voucher status is now marked as used, but kept in DB
                return response()->json([
                    'success' => true, 
                    'message' => 'Voucher activated', 
                    'expires_at' => $voucher->expires_at
                ]);
            }
            
            return response()->json(['success' => true, 'message' => 'Voucher already active']);
        }

        return response()->json(['success' => false, 'message' => 'Voucher not found']);
    }
}
