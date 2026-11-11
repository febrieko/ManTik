@extends('layouts.app')

@section('title', 'Detail Pelanggan - ' . $customer->name)
@section('page-title', 'Detail Pelanggan')

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;">
    <div class="page-header-title">
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <h2 style="margin:0;">{{ $customer->name }}</h2>
            @if($customer->status === 'active')
                <span class="badge badge-success">Aktif</span>
            @elseif($customer->status === 'suspended')
                <span class="badge badge-danger">Suspended</span>
            @else
                <span class="badge badge-warning">Non-Aktif</span>
            @endif
            @if($customer->customer_type === 'reseller')
                <span class="badge" style="background:rgba(168,85,247,0.15);color:#c084fc;border:1px solid rgba(168,85,247,0.3);">Reseller</span>
            @endif
        </div>
        <p style="margin-top:4px;">
            WhatsApp: <strong>{{ $customer->phone ?: 'Belum diisi' }}</strong>
            @if($customer->email) &bull; Email: {{ $customer->email }} @endif
            @if($customer->nik) &bull; NIK: {{ $customer->nik }} @endif
        </p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="{{ route('customers.index') }}" class="btn btn-outline">&larr; Daftar</a>
        @can('edit customers')
        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-outline">Edit Profil</a>
        <form action="{{ route('customers.toggle-status', $customer) }}" method="POST" style="display:inline;">
            @csrf
            <button type="submit" class="btn btn-outline" style="color:{{ $customer->status === 'active' ? '#f59e0b' : '#00e676' }};">
                {{ $customer->status === 'active' ? 'Suspend' : 'Aktifkan' }}
            </button>
        </form>
        @endcan
        @can('delete customers')
        <form action="{{ route('customers.destroy', $customer) }}" method="POST" style="display:inline;" onsubmit="return confirm('Hapus pelanggan {{ $customer->name }}?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline" style="color:#ef4444;">Hapus</button>
        </form>
        @endcan
    </div>
</div>

@if(session('success'))
<div class="alert alert-success mb-24" style="background:rgba(0,230,118,0.1);border:1px solid rgba(0,230,118,0.3);color:#00e676;padding:12px 16px;border-radius:10px;display:flex;align-items:center;gap:10px;">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    <span>{{ session('success') }}</span>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger mb-24" style="background:rgba(255,68,68,0.1);border:1px solid rgba(255,68,68,0.3);color:#ff4444;padding:12px 16px;border-radius:10px;display:flex;align-items:center;gap:10px;">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
    <span>{{ session('error') }}</span>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger mb-24" style="background:rgba(255,68,68,0.1);border:1px solid rgba(255,68,68,0.3);color:#ff4444;padding:12px 16px;border-radius:10px;">
    <ul style="margin:0 0 0 18px;">
        @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
        @endforeach
    </ul>
</div>
@endif

{{-- Grid Ringkasan & Top-Up --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px;margin-bottom:24px;">
    {{-- Card Info Profil & Layanan --}}
    <div class="card" style="padding:22px;">
        <h3 style="font-size:15px;margin-bottom:14px;">Informasi Akun &amp; Layanan</h3>
        <div style="display:grid;gap:10px;font-size:13px;">
            <div style="display:flex;justify-content:space-between;">
                <span style="color:var(--text-muted);">Akun Portal Login</span>
                @if($customer->user)
                    <span style="color:#00e676;font-weight:600;">Aktif (WA: {{ $customer->user->phone }})</span>
                @else
                    <span style="color:var(--text-muted);">Belum dibuat</span>
                @endif
            </div>
            <div style="display:flex;justify-content:space-between;">
                <span style="color:var(--text-muted);">Layanan PPPoE</span>
                <strong>{{ $customer->pppoeUsers->count() }} Akun</strong>
            </div>
            <div style="display:flex;justify-content:space-between;">
                <span style="color:var(--text-muted);">Layanan Hotspot</span>
                <strong>{{ $customer->hotspotUsers->count() }} Akun</strong>
            </div>
            <div style="display:flex;justify-content:space-between;">
                <span style="color:var(--text-muted);">Tagihan Belum Lunas</span>
                <strong style="color:{{ $billings->whereIn('status', ['unpaid','partial','overdue'])->count() > 0 ? '#f59e0b' : 'inherit' }};">
                    {{ $billings->whereIn('status', ['unpaid','partial','overdue'])->count() }} Invoice
                </strong>
            </div>
            <div style="border-top:1px solid var(--border-color);padding-top:10px;margin-top:4px;">
                <span style="color:var(--text-muted);display:block;font-size:11px;">Alamat:</span>
                <span>{{ $customer->address ?: '-' }}</span>
            </div>
        </div>
    </div>

    {{-- Card Saldo & Aksi Top-Up --}}
    <div class="card" style="padding:22px;border-left:3px solid #00e676;">
        <div style="font-size:13px;color:var(--text-muted);">Saldo Deposit Saat Ini</div>
        <div style="font-size:32px;font-weight:700;color:#00e676;margin:4px 0 16px;">
            Rp {{ number_format((float)$customer->balance, 0, ',', '.') }}
        </div>

        @can('edit customers')
        <form action="{{ route('customers.topup', $customer) }}" method="POST" style="display:grid;gap:10px;">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                <input type="number" name="amount" min="1000" step="500" class="form-control" placeholder="Nominal Top-up (Rp)" required>
                <select name="payment_method" class="form-control">
                    <option value="admin_cash">Tunai (Admin)</option>
                    <option value="bank_transfer">Transfer Bank</option>
                </select>
            </div>
            <input type="text" name="notes" class="form-control" placeholder="Catatan top-up (opsional)">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;">
                <label style="display:flex;align-items:center;gap:6px;font-size:12px;cursor:pointer;">
                    <input type="checkbox" name="send_wa" value="1" checked>
                    <span>Kirim struk ke WA</span>
                </label>
                <button type="submit" class="btn btn-primary">+ Top-Up Saldo</button>
            </div>
        </form>
        @endcan
    </div>

    {{-- Card Koreksi Saldo --}}
    @can('edit customers')
    <div class="card" style="padding:22px;">
        <h3 style="font-size:15px;margin-bottom:6px;">Koreksi / Penyesuaian Saldo</h3>
        <p style="font-size:12px;color:var(--text-muted);margin-bottom:12px;">Gunakan untuk koreksi manual (tambah/kurang) selain top-up reguler.</p>
        <form action="{{ route('customers.adjust-balance', $customer) }}" method="POST" style="display:grid;gap:10px;">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                <select name="adjustment_type" class="form-control">
                    <option value="credit">Tambah Saldo (+)</option>
                    <option value="debit">Kurangi Saldo (-)</option>
                </select>
                <input type="number" name="amount" min="1" class="form-control" placeholder="Nominal (Rp)" required>
            </div>
            <input type="text" name="notes" class="form-control" placeholder="Alasan koreksi (wajib diisi)" required>
            <div style="text-align:right;">
                <button type="submit" class="btn btn-outline">Simpan Koreksi</button>
            </div>
        </form>
    </div>
    @endcan
</div>

{{-- Buku Mutasi Saldo (Ledger) --}}
<div class="card mb-24">
    <div style="padding:18px 20px;border-bottom:1px solid var(--border-color);display:flex;justify-content:space-between;align-items:center;">
        <h3 style="margin:0;font-size:16px;">Buku Mutasi Saldo (Ledger)</h3>
        <span style="font-size:12px;color:var(--text-muted);">Riwayat keluar masuk dana secara real-time</span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Kategori</th>
                    <th>Keterangan</th>
                    <th>Nominal</th>
                    <th>Saldo Sebelum</th>
                    <th>Saldo Sesudah</th>
                    <th>Oleh</th>
                </tr>
            </thead>
            <tbody>
                @forelse($balanceTransactions as $trx)
                <tr>
                    <td style="white-space:nowrap;font-size:12px;color:var(--text-muted);">
                        {{ $trx->created_at->format('d/m/Y H:i') }}
                    </td>
                    <td>
                        <span class="badge" style="background:rgba(255,255,255,0.06);">{{ $trx->category_label }}</span>
                    </td>
                    <td style="font-size:13px;">{{ $trx->notes ?: '-' }}</td>
                    <td style="font-weight:700;color:{{ $trx->type === 'credit' ? '#00e676' : '#ef4444' }};white-space:nowrap;">
                        {{ $trx->type === 'credit' ? '+' : '-' }} Rp {{ number_format((float)$trx->amount, 0, ',', '.') }}
                    </td>
                    <td style="font-size:12px;color:var(--text-muted);">Rp {{ number_format((float)$trx->balance_before, 0, ',', '.') }}</td>
                    <td style="font-size:13px;font-weight:600;">Rp {{ number_format((float)$trx->balance_after, 0, ',', '.') }}</td>
                    <td style="font-size:12px;color:var(--text-muted);">{{ $trx->createdBy?->name ?: 'Sistem / Otomatis' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:32px;color:var(--text-muted);">Belum ada riwayat mutasi saldo.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($balanceTransactions->hasPages())
    <div style="padding:14px 20px;border-top:1px solid var(--border-color);">
        {{ $balanceTransactions->links() }}
    </div>
    @endif
</div>

{{-- Grid Layanan & Riwayat Top-up / Voucher WA --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(360px,1fr));gap:20px;">
    {{-- Riwayat Top-up --}}
    <div class="card">
        <div style="padding:16px 20px;border-bottom:1px solid var(--border-color);">
            <h3 style="margin:0;font-size:15px;">Riwayat Top-Up</h3>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Referensi</th>
                        <th>Nominal</th>
                        <th>Gateway / Metode</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topupTransactions as $topup)
                    <tr>
                        <td style="font-size:12px;">
                            <code>{{ $topup->reference_code }}</code><br>
                            <span style="color:var(--text-muted);font-size:11px;">{{ $topup->created_at->format('d/m/Y H:i') }}</span>
                        </td>
                        <td style="font-weight:600;">Rp {{ number_format((float)$topup->amount, 0, ',', '.') }}</td>
                        <td style="font-size:12px;">{{ strtoupper($topup->gateway) }} ({{ $topup->payment_method_label }})</td>
                        <td>
                            @if($topup->status === 'paid')
                                <span class="badge badge-success">Lunas</span>
                            @elseif($topup->status === 'pending')
                                <span class="badge badge-warning">Pending</span>
                                @can('edit customers')
                                <div style="display:flex;gap:4px;margin-top:6px;">
                                    <form action="{{ route('customers.topups.approve', $topup) }}" method="POST" onsubmit="return confirm('Konfirmasi pembayaran top-up Rp {{ number_format((float)$topup->amount, 0, ',', '.') }}?')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-primary" style="padding:3px 8px;font-size:11px;">Setujui</button>
                                    </form>
                                    <form action="{{ route('customers.topups.reject', $topup) }}" method="POST" onsubmit="return confirm('Batalkan pengajuan top-up ini?')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline" style="padding:3px 8px;font-size:11px;color:#ef4444;">Tolak</button>
                                    </form>
                                </div>
                                @endcan
                            @else
                                <span class="badge badge-danger">{{ ucfirst($topup->status) }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="text-align:center;padding:24px;color:var(--text-muted);">Belum ada transaksi top-up.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Riwayat Voucher WA --}}
    <div class="card">
        <div style="padding:16px 20px;border-bottom:1px solid var(--border-color);">
            <h3 style="margin:0;font-size:15px;">Riwayat Beli Voucher via WhatsApp</h3>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Paket</th>
                        <th>Kode Voucher</th>
                        <th>Potong Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($waVoucherRequests as $req)
                    <tr>
                        <td style="font-size:12px;color:var(--text-muted);">{{ $req->created_at->format('d/m/Y H:i') }}</td>
                        <td style="font-size:12px;">{{ $req->package?->name ?: $req->package_code }}</td>
                        <td>
                            @if($req->voucher_code)
                                <code>{{ $req->voucher_code }}</code>
                            @else
                                <span style="color:#ef4444;font-size:11px;">{{ $req->status }}</span>
                            @endif
                        </td>
                        <td style="font-weight:600;">
                            @if((float)$req->price_deducted > 0)
                                Rp {{ number_format((float)$req->price_deducted, 0, ',', '.') }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="text-align:center;padding:24px;color:var(--text-muted);">Belum ada pembelian voucher via WA.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

