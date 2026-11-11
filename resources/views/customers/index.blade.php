@extends('layouts.app')

@section('title', 'Manajemen Pelanggan')
@section('page-title', 'Daftar Pelanggan')

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;">
    <div class="page-header-title">
        <h2>Manajemen Pelanggan & Saldo Deposit</h2>
        <p>Kelola data pelanggan, saldo deposit voucher/tagihan, layanan aktif, dan akun login portal</p>
    </div>
    <div class="d-flex gap-8">
        @can('create customers')
        <a href="{{ route('customers.create') }}" class="btn btn-primary btn-lg" style="box-shadow: 0 4px 15px rgba(0, 212, 255, 0.3);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                <path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="8.5" cy="7" r="4"/>
                <line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/>
            </svg>
            Tambah Pelanggan
        </a>
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

{{-- Statistik Ringkas --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px;">
    <div class="card" style="padding:20px;border-left:3px solid #00d4ff;">
        <div style="font-size:13px;color:var(--text-muted);margin-bottom:6px;">Total Pelanggan</div>
        <div style="font-size:28px;font-weight:700;color:var(--text-primary);">{{ number_format($stats['total']) }}</div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">Terdaftar di sistem</div>
    </div>
    <div class="card" style="padding:20px;border-left:3px solid #25d366;">
        <div style="font-size:13px;color:var(--text-muted);margin-bottom:6px;">Pelanggan Aktif</div>
        <div style="font-size:28px;font-weight:700;color:#25d366;">{{ number_format($stats['active']) }}</div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">Siap bertransaksi</div>
    </div>
    <div class="card" style="padding:20px;border-left:3px solid #a855f7;">
        <div style="font-size:13px;color:var(--text-muted);margin-bottom:6px;">Mitra / Reseller</div>
        <div style="font-size:28px;font-weight:700;color:#a855f7;">{{ number_format($stats['resellers']) }}</div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">Agen voucher</div>
    </div>
    <div class="card" style="padding:20px;border-left:3px solid #f59e0b;">
        <div style="font-size:13px;color:var(--text-muted);margin-bottom:6px;">Total Saldo Deposit</div>
        <div style="font-size:24px;font-weight:700;color:#f59e0b;">Rp {{ number_format($stats['total_balance'], 0, ',', '.') }}</div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">Akumulasi saldo aktif</div>
    </div>
</div>

{{-- Filter Card --}}
<div class="card mb-20" style="padding:16px 20px;">
    <form action="{{ route('customers.index') }}" method="GET" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
        <div style="flex:1;min-width:220px;position:relative;">
            <input type="text" name="search" class="form-control" placeholder="Cari nama, nomor WhatsApp, email, atau NIK..." value="{{ request('search') }}" style="padding-left:36px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
        </div>
        <div style="min-width:150px;">
            <select name="customer_type" class="form-control" onchange="this.form.submit()">
                <option value="">Semua Tipe</option>
                <option value="regular" {{ request('customer_type') === 'regular' ? 'selected' : '' }}>Regular</option>
                <option value="reseller" {{ request('customer_type') === 'reseller' ? 'selected' : '' }}>Reseller / Agen</option>
            </select>
        </div>
        <div style="min-width:150px;">
            <select name="status" class="form-control" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Non-Aktif</option>
                <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
            </select>
        </div>
        <button type="submit" class="btn btn-outline">Filter</button>
        @if(request()->hasAny(['search', 'status', 'customer_type']))
        <a href="{{ route('customers.index') }}" class="btn btn-outline" style="color:var(--text-muted);">Reset</a>
        @endif
    </form>
</div>

{{-- Tabel Pelanggan --}}
<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Pelanggan</th>
                    <th>WhatsApp & Kontak</th>
                    <th>Tipe & Portal</th>
                    <th>Layanan</th>
                    <th>Saldo Deposit</th>
                    <th>Status</th>
                    <th class="text-center" width="220">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                <tr>
                    <td>
                        <div style="font-weight:600;color:var(--text-primary);font-size:14px;">
                            <a href="{{ route('customers.show', $customer) }}" style="color:inherit;text-decoration:none;">
                                {{ $customer->name }}
                            </a>
                        </div>
                        <div style="font-size:11px;color:var(--text-muted);">
                            {{ $customer->address ? Str::limit($customer->address, 40) : 'Alamat belum diisi' }}
                        </div>
                    </td>
                    <td>
                        @if($customer->phone)
                            <div style="font-weight:600;color:#25d366;font-size:13px;">{{ $customer->phone }}</div>
                        @else
                            <span style="color:var(--text-muted);font-size:12px;">Belum ada No. WA</span>
                        @endif
                        @if($customer->email)
                            <div style="font-size:11px;color:var(--text-muted);">{{ $customer->email }}</div>
                        @endif
                    </td>
                    <td>
                        @if($customer->customer_type === 'reseller')
                            <span class="badge" style="background:rgba(168,85,247,0.15);color:#c084fc;border:1px solid rgba(168,85,247,0.3);">Reseller</span>
                        @else
                            <span class="badge" style="background:rgba(0,212,255,0.12);color:#00d4ff;">Regular</span>
                        @endif
                        <div style="margin-top:4px;font-size:11px;">
                            @if($customer->user)
                                <span style="color:#00e676;">● Portal Aktif</span>
                            @else
                                <span style="color:var(--text-muted);">○ Tanpa Akun Portal</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <div style="font-size:12px;">
                            <span title="Akun PPPoE">PPPoE: <strong>{{ $customer->pppoe_users_count }}</strong></span>
                            &nbsp;|&nbsp;
                            <span title="Akun Hotspot">Hotspot: <strong>{{ $customer->hotspot_users_count }}</strong></span>
                        </div>
                        @if($customer->active_billings_count > 0)
                            <div style="font-size:11px;color:#f59e0b;margin-top:2px;">{{ $customer->active_billings_count }} tagihan belum lunas</div>
                        @endif
                    </td>
                    <td>
                        <div style="font-weight:700;font-size:14px;color:{{ (float)$customer->balance > 0 ? '#00e676' : 'var(--text-secondary)' }};">
                            Rp {{ number_format((float)$customer->balance, 0, ',', '.') }}
                        </div>
                    </td>
                    <td>
                        @if($customer->status === 'active')
                            <span class="badge badge-success">Aktif</span>
                        @elseif($customer->status === 'suspended')
                            <span class="badge badge-danger">Suspended</span>
                        @else
                            <span class="badge badge-warning">Non-Aktif</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap;">
                            <a href="{{ route('customers.show', $customer) }}" class="btn btn-sm btn-primary" title="Detail & Top-up Saldo">
                                Detail / Saldo
                            </a>
                            @can('edit customers')
                            <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-outline" title="Edit Pelanggan">
                                Edit
                            </a>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:48px;color:var(--text-muted);">
                        <div>Belum ada data pelanggan.</div>
                        @can('create customers')
                        <div style="margin-top:12px;">
                            <a href="{{ route('customers.create') }}" class="btn btn-primary btn-sm">Tambah Pelanggan Pertama</a>
                        </div>
                        @endcan
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($customers->hasPages())
    <div style="padding:16px 20px;border-top:1px solid var(--border-color);">
        {{ $customers->links() }}
    </div>
    @endif
</div>
@endsection

