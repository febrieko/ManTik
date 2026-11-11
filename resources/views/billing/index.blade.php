@extends('layouts.app')

@section('title', 'Tagihan & Billing')
@section('page-title', 'Tagihan Bulanan & Invoice')

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;">
    <div class="page-header-title">
        <h2>Tagihan & Billing Pelanggan</h2>
        <p>Kelola invoice bulanan PPPoE, pembayaran tunai/transfer/potong saldo deposit, dan pengingat WhatsApp</p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
        <form action="{{ route('billing.generate-monthly') }}" method="POST" style="display:flex;gap:8px;align-items:center;"
              onsubmit="return confirm('Generate tagihan bulanan untuk semua pelanggan PPPoE aktif yang belum ditagih di bulan tersebut?')">
            @csrf
            <input type="month" name="month" value="{{ request('month', now()->format('Y-m')) }}" class="form-control" style="width:165px;">
            <button type="submit" class="btn btn-outline" style="border-color:rgba(168,85,247,0.45);color:#c084fc;">
                ⚡ Generate Bulanan
            </button>
        </form>
        <a href="{{ route('billing.create') }}" class="btn btn-primary">
            + Buat Tagihan
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success mb-24" style="background:rgba(0,230,118,0.1);border:1px solid rgba(0,230,118,0.3);color:#00e676;padding:12px 16px;border-radius:10px;display:flex;align-items:center;gap:10px;">
    <span>{{ session('success') }}</span>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger mb-24" style="background:rgba(255,68,68,0.1);border:1px solid rgba(255,68,68,0.3);color:#ff4444;padding:12px 16px;border-radius:10px;display:flex;align-items:center;gap:10px;">
    <span>{{ session('error') }}</span>
</div>
@endif

{{-- Statistik Ringkas --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px;">
    <div class="card" style="padding:20px;border-left:3px solid #f59e0b;">
        <div style="font-size:13px;color:var(--text-muted);margin-bottom:6px;">Tagihan Belum Lunas</div>
        <div style="font-size:28px;font-weight:700;color:#f59e0b;">{{ number_format($stats['unpaid_count']) }}</div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">Unpaid / Partial / Overdue</div>
    </div>
    <div class="card" style="padding:20px;border-left:3px solid #ef4444;">
        <div style="font-size:13px;color:var(--text-muted);margin-bottom:6px;">Lewat Jatuh Tempo</div>
        <div style="font-size:28px;font-weight:700;color:#ef4444;">{{ number_format($stats['overdue_count']) }}</div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">Perlu pengingat / tindakan</div>
    </div>
    <div class="card" style="padding:20px;border-left:3px solid #00d4ff;">
        <div style="font-size:13px;color:var(--text-muted);margin-bottom:6px;">Total Piutang Aktif</div>
        <div style="font-size:24px;font-weight:700;color:#00d4ff;">Rp {{ number_format($stats['total_receivable'], 0, ',', '.') }}</div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">Sisa tagihan berjalan</div>
    </div>
    <div class="card" style="padding:20px;border-left:3px solid #25d366;">
        <div style="font-size:13px;color:var(--text-muted);margin-bottom:6px;">Terbayar Bulan Ini</div>
        <div style="font-size:24px;font-weight:700;color:#25d366;">Rp {{ number_format($stats['paid_this_month'], 0, ',', '.') }}</div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">{{ now()->translatedFormat('F Y') }}</div>
    </div>
</div>

{{-- Filter --}}
<div class="card mb-20" style="padding:16px 20px;">
    <form action="{{ route('billing.index') }}" method="GET" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
        <div style="flex:1;min-width:220px;">
            <input type="text" name="search" class="form-control" placeholder="Cari No. Invoice, nama pelanggan, atau nomor WA..." value="{{ request('search') }}">
        </div>
        <div style="min-width:160px;">
            <select name="status" class="form-control" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="unpaid" {{ request('status') === 'unpaid' ? 'selected' : '' }}>Belum Bayar (Unpaid)</option>
                <option value="partial" {{ request('status') === 'partial' ? 'selected' : '' }}>Cicilan (Partial)</option>
                <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>Jatuh Tempo (Overdue)</option>
                <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Lunas (Paid)</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
            </select>
        </div>
        <div style="min-width:160px;">
            <input type="month" name="month" class="form-control" value="{{ request('month') }}" onchange="this.form.submit()">
        </div>
        <button type="submit" class="btn btn-outline">Filter</button>
        @if(request()->hasAny(['search', 'status', 'month']))
            <a href="{{ route('billing.index') }}" class="btn btn-outline" style="color:var(--text-muted);">Reset</a>
        @endif
    </form>
</div>

{{-- Tabel Tagihan --}}
<div class="card" style="padding:0;overflow:hidden;">
    <div style="overflow-x:auto;">
        <table class="table" style="width:100%;border-collapse:collapse;">
            <thead>
                <tr>
                    <th style="padding:14px 16px;text-align:left;">Invoice</th>
                    <th style="padding:14px 16px;text-align:left;">Pelanggan</th>
                    <th style="padding:14px 16px;text-align:left;">Paket & Periode</th>
                    <th style="padding:14px 16px;text-align:right;">Total / Sisa</th>
                    <th style="padding:14px 16px;text-align:left;">Jatuh Tempo</th>
                    <th style="padding:14px 16px;text-align:center;">Status</th>
                    <th style="padding:14px 16px;text-align:right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($billings as $bill)
                <tr style="border-top:1px solid var(--border-color);">
                    <td style="padding:14px 16px;">
                        <a href="{{ route('billing.invoice', $bill) }}" style="font-weight:700;color:#00d4ff;text-decoration:none;font-family:monospace;">
                            {{ $bill->invoice_number }}
                        </a>
                        <div style="font-size:11px;color:var(--text-muted);margin-top:2px;">
                            {{ $bill->created_at->format('d M Y') }}
                        </div>
                    </td>
                    <td style="padding:14px 16px;">
                        @if($bill->customer)
                            <a href="{{ route('customers.show', $bill->customer) }}" style="font-weight:600;color:var(--text-primary);text-decoration:none;">
                                {{ $bill->customer->name }}
                            </a>
                            <div style="font-size:12px;color:var(--text-muted);">{{ $bill->customer->phone ?? '-' }}</div>
                            <div style="font-size:11px;color:#f59e0b;">Saldo: Rp {{ number_format((float)$bill->customer->balance, 0, ',', '.') }}</div>
                        @else
                            <span style="color:var(--text-muted);">-</span>
                        @endif
                    </td>
                    <td style="padding:14px 16px;">
                        <div style="font-weight:500;">{{ $bill->package->name ?? 'Tagihan Manual' }}</div>
                        <div style="font-size:12px;color:var(--text-muted);">
                            {{ $bill->period_start->format('d M') }} – {{ $bill->period_end->format('d M Y') }}
                        </div>
                    </td>
                    <td style="padding:14px 16px;text-align:right;">
                        <div style="font-weight:700;">Rp {{ number_format((float)$bill->amount, 0, ',', '.') }}</div>
                        @if($bill->remaining_amount > 0 && (float)$bill->paid_amount > 0)
                            <div style="font-size:11px;color:#f59e0b;">Sisa: Rp {{ number_format($bill->remaining_amount, 0, ',', '.') }}</div>
                        @elseif($bill->status === 'paid')
                            <div style="font-size:11px;color:#25d366;">Via {{ strtoupper($bill->payment_method ?? '-') }}</div>
                        @endif
                    </td>
                    <td style="padding:14px 16px;">
                        <div style="color:{{ $bill->isOverdue() ? '#ef4444' : 'var(--text-primary)' }};font-weight:{{ $bill->isOverdue() ? '600' : '400' }};">
                            {{ $bill->due_date->format('d M Y') }}
                        </div>
                        @if($bill->isOverdue())
                            <span style="font-size:11px;color:#ef4444;">Terlambat {{ $bill->due_date->diffInDays(now()) }} hari</span>
                        @endif
                    </td>
                    <td style="padding:14px 16px;text-align:center;">
                        @php
                            $badgeStyle = match($bill->status) {
                                'paid' => 'background:rgba(37,211,102,0.15);color:#25d366;border:1px solid rgba(37,211,102,0.35);',
                                'partial' => 'background:rgba(0,212,255,0.15);color:#00d4ff;border:1px solid rgba(0,212,255,0.35);',
                                'overdue' => 'background:rgba(239,68,68,0.15);color:#ef4444;border:1px solid rgba(239,68,68,0.35);',
                                'cancelled' => 'background:rgba(148,163,184,0.15);color:#94a3b8;border:1px solid rgba(148,163,184,0.3);',
                                default => 'background:rgba(245,158,11,0.15);color:#f59e0b;border:1px solid rgba(245,158,11,0.35);',
                            };
                        @endphp
                        <span style="padding:4px 10px;border-radius:999px;font-size:12px;font-weight:600;{{ $badgeStyle }}">
                            {{ strtoupper($bill->status) }}
                        </span>
                    </td>
                    <td style="padding:14px 16px;text-align:right;white-space:nowrap;">
                        <a href="{{ route('billing.invoice', $bill) }}" class="btn btn-sm btn-primary">
                            {{ $bill->status === 'paid' ? 'Lihat Invoice' : 'Detail / Bayar' }}
                        </a>
                        @if($bill->status !== 'paid' && $bill->customer?->phone)
                        <form action="{{ route('billing.remind', $bill) }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline" title="Kirim Pengingat WA">🔔 WA</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="padding:40px;text-align:center;color:var(--text-muted);">
                        Belum ada data tagihan. Klik <strong>⚡ Generate Bulanan</strong> atau <strong>+ Buat Tagihan</strong> untuk memulai.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($billings->hasPages())
    <div style="padding:16px 20px;border-top:1px solid var(--border-color);">
        {{ $billings->links() }}
    </div>
    @endif
</div>
@endsection

