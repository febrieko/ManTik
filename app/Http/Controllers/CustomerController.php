<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\TopupTransaction;
use App\Models\User;
use App\Services\CustomerBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function __construct(protected CustomerBalanceService $balanceService)
    {
    }

    /**
     * Daftar seluruh pelanggan.
     */
    public function index(Request $request)
    {
        $query = Customer::with(['user'])
            ->withCount(['pppoeUsers', 'hotspotUsers', 'activeBillings'])
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = trim($request->get('search'));
            $normalizedSearch = Customer::normalizePhoneNumber($search);

            $query->where(function ($q) use ($search, $normalizedSearch) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%");
                if ($normalizedSearch && $normalizedSearch !== $search) {
                    $q->orWhere('phone', 'like', "%{$normalizedSearch}%");
                }
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('customer_type')) {
            $query->where('customer_type', $request->get('customer_type'));
        }

        $customers = $query->paginate(20)->withQueryString();

        $stats = [
            'total'         => Customer::count(),
            'active'        => Customer::where('status', 'active')->count(),
            'resellers'     => Customer::where('customer_type', 'reseller')->count(),
            'total_balance' => (float) Customer::sum('balance'),
        ];

        return view('customers.index', compact('customers', 'stats'));
    }

    /**
     * Form tambah pelanggan baru.
     */
    public function create()
    {
        abort_unless(auth()->user()->can('create customers'), 403);
        return view('customers.create');
    }

    /**
     * Simpan pelanggan baru (beserta opsi akun portal login WA + password & saldo awal).
     */
    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('create customers'), 403);

        // Normalisasi nomor WA sebelum validasi unik
        $normalizedPhone = Customer::normalizePhoneNumber($request->input('phone'));
        $request->merge(['phone' => $normalizedPhone]);

        $createPortal = $request->boolean('create_portal_account');

        $rules = [
            'name'            => ['required', 'string', 'max:255'],
            'phone'           => [
                $createPortal ? 'required' : 'nullable',
                'string',
                'max:20',
                Rule::unique('customers', 'phone'),
            ],
            'email'           => ['nullable', 'email', 'max:255'],
            'nik'             => ['nullable', 'string', 'max:30'],
            'address'         => ['nullable', 'string', 'max:1000'],
            'telegram_chat_id'=> ['nullable', 'string', 'max:100'],
            'customer_type'   => ['required', Rule::in(['regular', 'reseller'])],
            'status'          => ['required', Rule::in(['active', 'inactive', 'suspended'])],
            'initial_balance' => ['nullable', 'numeric', 'min:0', 'max:50000000'],
        ];

        if ($createPortal) {
            $rules['phone'][] = Rule::unique('users', 'phone');
            $rules['portal_password'] = ['required', 'string', 'min:6', 'max:100'];
            if ($request->filled('email')) {
                $rules['email'][] = Rule::unique('users', 'email');
            }
        }

        $validated = $request->validate($rules, [
            'phone.unique'           => 'Nomor WhatsApp ini sudah terdaftar pada pelanggan atau akun lain.',
            'phone.required'         => 'Nomor WhatsApp wajib diisi untuk membuat akun login portal pelanggan.',
            'portal_password.required' => 'Password portal wajib diisi jika membuat akun login pelanggan.',
            'portal_password.min'    => 'Password portal minimal 6 karakter.',
        ]);

        $customer = DB::transaction(function () use ($validated, $createPortal, $request) {
            $userId = null;

            if ($createPortal) {
                $user = User::create([
                    'name'      => $validated['name'],
                    'email'     => $validated['email'] ?? null,
                    'phone'     => $validated['phone'],
                    'password'  => Hash::make($validated['portal_password']),
                    'is_active' => $validated['status'] === 'active',
                ]);
                $user->assignRole('customer');
                $userId = $user->id;
            }

            return Customer::create([
                'user_id'          => $userId,
                'name'             => $validated['name'],
                'phone'            => $validated['phone'],
                'email'            => $validated['email'] ?? null,
                'nik'              => $validated['nik'] ?? null,
                'address'          => $validated['address'] ?? null,
                'telegram_chat_id' => $validated['telegram_chat_id'] ?? null,
                'customer_type'    => $validated['customer_type'],
                'status'           => $validated['status'],
                'balance'          => 0,
            ]);
        });

        // Proses saldo awal jika diisi
        $initialBalance = (float) ($validated['initial_balance'] ?? 0);
        if ($initialBalance > 0) {
            $this->balanceService->topupManual(
                $customer,
                $initialBalance,
                'admin_cash',
                'Saldo awal pendaftaran pelanggan',
                auth()->id(),
                $request->boolean('send_wa_topup', false)
            );
        }

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', "Pelanggan {$customer->name} berhasil ditambahkan.");
    }

    /**
     * Halaman detail 360 derajat pelanggan.
     */
    public function show(Customer $customer)
    {
        $customer->load([
            'user',
            'pppoeUsers.package',
            'pppoeUsers.router',
            'hotspotUsers.package',
            'hotspotUsers.router',
        ]);

        $balanceTransactions = $customer->balanceTransactions()
            ->with('createdBy')
            ->paginate(15, ['*'], 'ledger_page')
            ->withQueryString();

        $topupTransactions = $customer->topupTransactions()
            ->with('createdBy')
            ->limit(15)
            ->get();

        $waVoucherRequests = $customer->waVoucherRequests()
            ->with('package')
            ->limit(15)
            ->get();

        $billings = $customer->billings()
            ->with('package')
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        return view('customers.show', compact(
            'customer',
            'balanceTransactions',
            'topupTransactions',
            'waVoucherRequests',
            'billings'
        ));
    }

    /**
     * Form edit data pelanggan.
     */
    public function edit(Customer $customer)
    {
        abort_unless(auth()->user()->can('edit customers'), 403);
        $customer->load('user');
        return view('customers.edit', compact('customer'));
    }

    /**
     * Perbarui data pelanggan (dan sinkronkan ke akun portal jika ada/dibuat).
     */
    public function update(Request $request, Customer $customer)
    {
        abort_unless(auth()->user()->can('edit customers'), 403);

        $normalizedPhone = Customer::normalizePhoneNumber($request->input('phone'));
        $request->merge(['phone' => $normalizedPhone]);

        $createPortal = !$customer->user_id && $request->boolean('create_portal_account');
        $hasPortal    = (bool) $customer->user_id;

        $rules = [
            'name'             => ['required', 'string', 'max:255'],
            'phone'            => [
                ($createPortal || $hasPortal) ? 'required' : 'nullable',
                'string',
                'max:20',
                Rule::unique('customers', 'phone')->ignore($customer->id),
            ],
            'email'            => ['nullable', 'email', 'max:255'],
            'nik'              => ['nullable', 'string', 'max:30'],
            'address'          => ['nullable', 'string', 'max:1000'],
            'telegram_chat_id' => ['nullable', 'string', 'max:100'],
            'customer_type'    => ['required', Rule::in(['regular', 'reseller'])],
            'status'           => ['required', Rule::in(['active', 'inactive', 'suspended'])],
        ];

        if ($createPortal) {
            $rules['phone'][] = Rule::unique('users', 'phone');
            $rules['portal_password'] = ['required', 'string', 'min:6', 'max:100'];
            if ($request->filled('email')) {
                $rules['email'][] = Rule::unique('users', 'email');
            }
        } elseif ($hasPortal) {
            $rules['phone'][] = Rule::unique('users', 'phone')->ignore($customer->user_id);
            $rules['portal_password'] = ['nullable', 'string', 'min:6', 'max:100'];
            if ($request->filled('email')) {
                $rules['email'][] = Rule::unique('users', 'email')->ignore($customer->user_id);
            }
        }

        $validated = $request->validate($rules, [
            'phone.unique'             => 'Nomor WhatsApp ini sudah terdaftar pada pelanggan atau akun lain.',
            'phone.required'           => 'Nomor WhatsApp wajib diisi untuk akun login portal pelanggan.',
            'portal_password.required' => 'Password portal wajib diisi untuk membuat akun login pelanggan.',
            'portal_password.min'      => 'Password portal minimal 6 karakter.',
        ]);

        DB::transaction(function () use ($customer, $validated, $createPortal) {
            $userId = $customer->user_id;

            if ($createPortal) {
                $user = User::create([
                    'name'      => $validated['name'],
                    'email'     => $validated['email'] ?? null,
                    'phone'     => $validated['phone'],
                    'password'  => Hash::make($validated['portal_password']),
                    'is_active' => $validated['status'] === 'active',
                ]);
                $user->assignRole('customer');
                $userId = $user->id;
            } elseif ($customer->user) {
                $userData = [
                    'name'      => $validated['name'],
                    'email'     => $validated['email'] ?? null,
                    'phone'     => $validated['phone'],
                    'is_active' => $validated['status'] === 'active',
                ];
                if (!empty($validated['portal_password'])) {
                    $userData['password'] = Hash::make($validated['portal_password']);
                }
                $customer->user->update($userData);
            }

            $customer->update([
                'user_id'          => $userId,
                'name'             => $validated['name'],
                'phone'            => $validated['phone'],
                'email'            => $validated['email'] ?? null,
                'nik'              => $validated['nik'] ?? null,
                'address'          => $validated['address'] ?? null,
                'telegram_chat_id' => $validated['telegram_chat_id'] ?? null,
                'customer_type'    => $validated['customer_type'],
                'status'           => $validated['status'],
            ]);
        });

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', "Data pelanggan {$customer->name} berhasil diperbarui.");
    }

    /**
     * Top-up saldo pelanggan oleh Admin (manual cash / transfer bank).
     */
    public function topup(Request $request, Customer $customer)
    {
        abort_unless(auth()->user()->can('edit customers'), 403);

        $validated = $request->validate([
            'amount'         => ['required', 'numeric', 'min:1000', 'max:50000000'],
            'payment_method' => ['required', Rule::in(['admin_cash', 'bank_transfer'])],
            'notes'          => ['nullable', 'string', 'max:255'],
        ], [
            'amount.min' => 'Minimal top-up saldo adalah Rp 1.000.',
        ]);

        $topup = $this->balanceService->topupManual(
            $customer,
            (float) $validated['amount'],
            $validated['payment_method'],
            $validated['notes'] ?? null,
            auth()->id(),
            $request->boolean('send_wa', true)
        );

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'Top-up saldo sebesar Rp ' . number_format((float) $topup->amount, 0, ',', '.') . " berhasil (#{$topup->reference_code}).");
    }

    /**
     * Koreksi / penyesuaian saldo (credit/debit) oleh Admin.
     */
    public function adjustBalance(Request $request, Customer $customer)
    {
        abort_unless(auth()->user()->can('edit customers'), 403);

        $validated = $request->validate([
            'adjustment_type' => ['required', Rule::in(['credit', 'debit'])],
            'amount'          => ['required', 'numeric', 'min:1', 'max:50000000'],
            'notes'           => ['required', 'string', 'max:255'],
        ]);

        try {
            if ($validated['adjustment_type'] === 'credit') {
                $this->balanceService->credit(
                    $customer,
                    (float) $validated['amount'],
                    'adjustment',
                    $validated['notes'],
                    null,
                    null,
                    auth()->id()
                );
            } else {
                $this->balanceService->debit(
                    $customer,
                    (float) $validated['amount'],
                    'adjustment',
                    $validated['notes'],
                    null,
                    null,
                    auth()->id()
                );
            }
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'Penyesuaian saldo pelanggan berhasil disimpan.');
    }

    /**
     * Toggle status aktif/suspended pelanggan.
     */
    public function toggleStatus(Customer $customer)
    {
        abort_unless(auth()->user()->can('edit customers'), 403);

        $newStatus = $customer->status === 'active' ? 'suspended' : 'active';
        $customer->update(['status' => $newStatus]);

        if ($customer->user) {
            $customer->user->update(['is_active' => $newStatus === 'active']);
        }

        $label = $newStatus === 'active' ? 'diaktifkan' : 'disuspend';
        return back()->with('success', "Status pelanggan {$customer->name} berhasil {$label}.");
    }

    /**
     * Hapus pelanggan (soft delete dengan pengamanan saldo).
     */
    public function destroy(Customer $customer)
    {
        abort_unless(auth()->user()->can('delete customers'), 403);

        if ((float) $customer->balance > 0) {
            return back()->with('error', "Pelanggan {$customer->name} masih memiliki saldo deposit sebesar Rp " . number_format((float) $customer->balance, 0, ',', '.') . ". Kosongkan saldo terlebih dahulu sebelum menghapus.");
        }

        DB::transaction(function () use ($customer) {
            if ($customer->user) {
                $customer->user->update(['is_active' => false]);
            }
            $customer->delete();
        });

        return redirect()
            ->route('customers.index')
            ->with('success', "Pelanggan {$customer->name} berhasil dihapus.");
    }

    /**
     * Setujui (mark paid) pengajuan top-up pending dari pelanggan.
     */
    public function approveTopup(TopupTransaction $topup)
    {
        abort_unless(auth()->user()->can('edit customers'), 403);

        try {
            $this->balanceService->approveTopup($topup, auth()->id());
            return back()->with('success', "Top-up #{$topup->reference_code} berhasil dikonfirmasi dan saldo pelanggan telah ditambahkan.");
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Tolak/batalkan pengajuan top-up pending.
     */
    public function rejectTopup(TopupTransaction $topup)
    {
        abort_unless(auth()->user()->can('edit customers'), 403);

        if ($topup->status !== 'pending') {
            return back()->with('error', "Top-up #{$topup->reference_code} sudah diproses sebelumnya.");
        }

        $topup->update(['status' => 'failed']);
        return back()->with('success', "Pengajuan top-up #{$topup->reference_code} telah dibatalkan.");
    }
}
