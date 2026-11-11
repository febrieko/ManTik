<?php

namespace App\Http\Controllers;

use App\Models\Billing;
use App\Models\Customer;
use App\Models\Package;
use App\Services\BillingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function __construct(protected BillingService $billingService)
    {
    }

    /**
     * Daftar seluruh tagihan & ringkasan status pembayaran.
     */
    public function index(Request $request)
    {
        $query = Billing::with(['customer', 'package', 'createdBy']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('month')) {
            try {
                $monthDate = Carbon::createFromFormat('Y-m', $request->month);
                $query->whereYear('period_start', $monthDate->year)
                      ->whereMonth('period_start', $monthDate->month);
            } catch (\Throwable) {
                // Abaikan filter bulan jika format tidak valid
            }
        }

        $billings = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'unpaid_count'   => Billing::whereIn('status', ['unpaid', 'partial', 'overdue'])->count(),
            'overdue_count'  => Billing::where(function ($q) {
                $q->where('status', 'overdue')
                  ->orWhere(function ($sub) {
                      $sub->whereIn('status', ['unpaid', 'partial'])
                          ->whereDate('due_date', '<', now()->toDateString());
                  });
            })->count(),
            'total_receivable' => (float) Billing::whereIn('status', ['unpaid', 'partial', 'overdue'])
                ->selectRaw('SUM(GREATEST(amount - paid_amount, 0)) as total')
                ->value('total'),
            'paid_this_month' => (float) Billing::where('status', 'paid')
                ->whereYear('paid_at', now()->year)
                ->whereMonth('paid_at', now()->month)
                ->sum('paid_amount'),
        ];

        return view('billing.index', compact('billings', 'stats'));
    }

    /**
     * Form pembuatan tagihan manual.
     */
    public function create(Request $request)
    {
        $customers = Customer::where('status', 'active')
            ->with(['pppoeUsers.package'])
            ->orderBy('name')
            ->get();
        $packages = Package::where('is_active', true)->orderBy('name')->get();
        $selectedCustomerId = $request->integer('customer_id') ?: null;

        return view('billing.create', compact('customers', 'packages', 'selectedCustomerId'));
    }

    /**
     * Simpan tagihan baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id'  => 'required|exists:customers,id',
            'package_id'   => 'nullable|exists:packages,id',
            'amount'       => 'required|numeric|min:1000',
            'period_start' => 'required|date',
            'period_end'   => 'required|date|after_or_equal:period_start',
            'due_date'     => 'required|date',
            'notes'        => 'nullable|string|max:1000',
        ]);

        $billing = Billing::create([
            'customer_id'  => $validated['customer_id'],
            'package_id'   => $validated['package_id'] ?: null,
            'amount'       => $validated['amount'],
            'paid_amount'  => 0,
            'period_start' => $validated['period_start'],
            'period_end'   => $validated['period_end'],
            'due_date'     => $validated['due_date'],
            'status'       => 'unpaid',
            'notes'        => $validated['notes'] ?? null,
            'created_by'   => auth()->id(),
        ]);

        return redirect()->route('billing.invoice', $billing)
            ->with('success', "Tagihan {$billing->invoice_number} berhasil dibuat.");
    }

    /**
     * Detail tagihan (redirect/tampilkan tampilan invoice).
     */
    public function show(Billing $billing)
    {
        return $this->invoice($billing);
    }

    /**
     * Tampilan Invoice & Aksi Pembayaran.
     */
    public function invoice(Billing $billing)
    {
        $billing->load(['customer', 'package', 'createdBy']);
        return view('billing.invoice', compact('billing'));
    }

    /**
     * Proses pembayaran tagihan (tunai, transfer, atau potong saldo deposit).
     */
    public function pay(Request $request, Billing $billing)
    {
        $validated = $request->validate([
            'amount'         => 'required|numeric|min:1',
            'payment_method' => 'required|in:cash,transfer,balance',
            'notes'          => 'nullable|string|max:500',
        ]);

        try {
            $updated = $this->billingService->payInvoice(
                $billing,
                (float) $validated['amount'],
                $validated['payment_method'],
                auth()->id(),
                $validated['notes'] ?? null
            );

            $statusMsg = $updated->status === 'paid' ? 'LUNAS' : 'Terbayar Sebagian';
            return back()->with('success', "Pembayaran invoice {$updated->invoice_number} berhasil dicatat ({$statusMsg}).");
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memproses pembayaran: ' . $e->getMessage());
        }
    }

    /**
     * Generate tagihan bulanan otomatis untuk semua pelanggan PPPoE aktif.
     */
    public function generateMonthly(Request $request)
    {
        $periodDate = null;
        if ($request->filled('month')) {
            $request->validate(['month' => 'date_format:Y-m']);
            $periodDate = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();
        }

        $result = $this->billingService->generateMonthlyBillings($periodDate, auth()->id());

        $msg = "Generate tagihan selesai: {$result['created']} tagihan baru dibuat, {$result['skipped']} dilewati (sudah ada/nonaktif).";
        if ($result['overdue_updated'] > 0) {
            $msg .= " {$result['overdue_updated']} tagihan lewat jatuh tempo ditandai Overdue.";
        }

        return redirect()->route('billing.index')->with('success', $msg);
    }

    /**
     * Kirim pengingat tagihan via WhatsApp.
     */
    public function sendReminder(Billing $billing)
    {
        $billing->load(['customer', 'package']);
        $res = $this->billingService->sendReminder($billing);

        if ($res['success'] ?? false) {
            return back()->with('success', "Pengingat tagihan {$billing->invoice_number} berhasil dikirim ke {$billing->customer->phone}.");
        }

        return back()->with('error', 'Gagal mengirim pengingat WhatsApp: ' . ($res['error'] ?? 'Periksa konfigurasi WA Gateway'));
    }

    /**
     * Hapus tagihan yang belum dibayar.
     */
    public function destroy(Billing $billing)
    {
        if ($billing->status === 'paid' || (float) $billing->paid_amount > 0) {
            return back()->with('error', 'Tagihan yang sudah memiliki riwayat pembayaran tidak dapat dihapus.');
        }

        $inv = $billing->invoice_number;
        $billing->delete();

        return redirect()->route('billing.index')
            ->with('success', "Tagihan {$inv} berhasil dihapus.");
    }
}
