<?php

namespace App\Http\Controllers;

use App\Models\NotificationLog;
use App\Models\Setting;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class NotificationController extends Controller
{
    /**
     * Authorize user access: only Super Admin, Admin, and Teknisi
     */
    protected function authorizeAccess(): void
    {
        $user = auth()->user();
        if (!$user || (!$user->isSuperAdmin() && !$user->isAdmin() && !$user->isTeknisi())) {
            abort(403, 'Akses ditolak. Halaman notifikasi hanya dapat diakses oleh Super Admin, Admin, dan Teknisi.');
        }
    }

    /**
     * Display Notification Center: Logs, Settings, and Test Tools
     */
    public function index(Request $request)
    {
        $this->authorizeAccess();
        $user = auth()->user();

        // 1. Logs Query with Filters
        $query = NotificationLog::with(['router', 'customer'])
            ->orderByDesc('created_at');

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('channel')) {
            $query->where('channel', $request->input('channel'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', Carbon::parse($request->input('date_from'))->startOfDay());
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', Carbon::parse($request->input('date_to'))->endOfDay());
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('recipient', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(20)->withQueryString();

        // 2. Summary Statistics
        $stats = [
            'total'          => NotificationLog::count(),
            'sent'           => NotificationLog::where('status', 'sent')->count(),
            'failed'         => NotificationLog::where('status', 'failed')->count(),
            'whatsapp_count' => NotificationLog::where('channel', 'whatsapp')->where('status', 'sent')->count(),
            'telegram_count' => NotificationLog::where('channel', 'telegram')->where('status', 'sent')->count(),
            'today_count'    => NotificationLog::whereDate('created_at', today())->count(),
        ];

        // 3. Current Settings
        $settings = [
            // Router alerts
            'router_alert_enabled'           => Setting::get('router_alert_enabled', true),
            'router_alert_channel'           => Setting::get('router_alert_channel', 'both'), // 'whatsapp', 'telegram', 'both'
            'whatsapp_admin_phone'           => Setting::get('whatsapp_admin_phone', config('whatsapp.admin_phone')),
            'telegram_admin_chat_id'         => Setting::get('telegram_admin_chat_id', ''),

            // Customer voucher alerts
            'customer_voucher_alert_enabled' => Setting::get('customer_voucher_alert_enabled', true),
            'customer_voucher_alert_channel' => Setting::get('customer_voucher_alert_channel', 'both'),
            'customer_voucher_expiry_hours'  => Setting::get('customer_voucher_expiry_hours', 2),

            // Customer balance alerts
            'customer_balance_alert_enabled' => Setting::get('customer_balance_alert_enabled', true),
            'customer_balance_alert_channel' => Setting::get('customer_balance_alert_channel', 'both'),
            'customer_balance_threshold'     => Setting::get('customer_balance_threshold', 10000),
        ];

        // 4. Gateway Integration Health Status
        $gateways = [
            'whatsapp_configured' => !empty(config('whatsapp.api_url')) && !empty(config('whatsapp.api_key')),
            'telegram_configured' => !empty(config('services.telegram.bot_token')),
        ];

        return view('notifications.index', compact('logs', 'stats', 'settings', 'gateways', 'user'));
    }

    /**
     * Save Notification Settings
     */
    public function updateSettings(Request $request)
    {
        $this->authorizeAccess();

        $validated = $request->validate([
            // Router alerts
            'router_alert_enabled'           => 'nullable|boolean',
            'router_alert_channel'           => 'required|in:whatsapp,telegram,both',
            'whatsapp_admin_phone'           => 'nullable|string|max:25',
            'telegram_admin_chat_id'         => 'nullable|string|max:50',

            // Customer voucher alerts
            'customer_voucher_alert_enabled' => 'nullable|boolean',
            'customer_voucher_alert_channel' => 'required|in:whatsapp,telegram,both',
            'customer_voucher_expiry_hours'  => 'required|integer|min:1|max:72',

            // Customer balance alerts
            'customer_balance_alert_enabled' => 'nullable|boolean',
            'customer_balance_alert_channel' => 'required|in:whatsapp,telegram,both',
            'customer_balance_threshold'     => 'required|numeric|min:0',

            // Personal contact for current user
            'my_phone'                       => 'nullable|string|max:25',
            'my_telegram_chat_id'            => 'nullable|string|max:50',
        ]);

        Setting::set('router_alert_enabled', $request->has('router_alert_enabled'), 'notification');
        Setting::set('router_alert_channel', $validated['router_alert_channel'], 'notification');
        Setting::set('whatsapp_admin_phone', $validated['whatsapp_admin_phone'] ?? '', 'notification');
        Setting::set('telegram_admin_chat_id', $validated['telegram_admin_chat_id'] ?? '', 'notification');

        Setting::set('customer_voucher_alert_enabled', $request->has('customer_voucher_alert_enabled'), 'notification');
        Setting::set('customer_voucher_alert_channel', $validated['customer_voucher_alert_channel'], 'notification');
        Setting::set('customer_voucher_expiry_hours', (int) $validated['customer_voucher_expiry_hours'], 'notification');

        Setting::set('customer_balance_alert_enabled', $request->has('customer_balance_alert_enabled'), 'notification');
        Setting::set('customer_balance_alert_channel', $validated['customer_balance_alert_channel'], 'notification');
        Setting::set('customer_balance_threshold', (float) $validated['customer_balance_threshold'], 'notification');

        // Update personal user contact
        $user = auth()->user();
        $user->update([
            'phone'            => $validated['my_phone'] ?? $user->phone,
            'telegram_chat_id' => $validated['my_telegram_chat_id'] ?? $user->telegram_chat_id,
        ]);

        return redirect()->route('notifications.index', ['tab' => 'settings'])
            ->with('success', 'Pengaturan notifikasi WhatsApp dan Telegram berhasil disimpan!');
    }

    /**
     * Send test notification via WhatsApp or Telegram
     */
    public function test(Request $request, NotificationService $notificationService)
    {
        $this->authorizeAccess();

        $validated = $request->validate([
            'channel'            => 'required|in:whatsapp,telegram,both',
            'target_phone'       => 'nullable|string|max:25',
            'target_telegram_id' => 'nullable|string|max:50',
        ]);

        $results = $notificationService->sendTestNotification(
            $validated['channel'],
            $validated['target_phone'] ?? null,
            $validated['target_telegram_id'] ?? null
        );

        if ($request->wantsJson()) {
            return response()->json($results);
        }

        if ($results['success']) {
            return redirect()->route('notifications.index', ['tab' => 'test'])
                ->with('success', 'Pesan uji coba notifikasi berhasil dikirim!');
        }

        $errorMsg = 'Gagal mengirim pesan uji coba. Periksa kredensial API WhatsApp/Telegram atau nomor/ID tujuan.';
        if (!empty($results['whatsapp']['error'])) {
            $errorMsg .= ' [WA: ' . $results['whatsapp']['error'] . ']';
        }
        if (!empty($results['telegram']['error'])) {
            $errorMsg .= ' [TG: ' . $results['telegram']['error'] . ']';
        }

        return redirect()->route('notifications.index', ['tab' => 'test'])
            ->with('error', $errorMsg);
    }

    /**
     * Trigger manual customer alert scan
     */
    public function scanCustomers(Request $request)
    {
        $this->authorizeAccess();

        Artisan::call('mantik:check-customer-notifications', ['--force' => true]);
        $output = Artisan::output();

        return redirect()->route('notifications.index')
            ->with('success', 'Pemindaian notifikasi voucher & saldo pelanggan selesai diproses! ' . trim($output));
    }
}
