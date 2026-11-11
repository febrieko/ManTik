@extends('layouts.app')

@section('title', 'Order Voucher Publik')
@section('page-title', 'Riwayat Order Voucher Publik (Guest Checkout)')

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;">
    <div class="page-header-title">
        <h2>Pembelian Voucher Publik (Guest Checkout)</h2>
        <p>Pantau transaksi pembelian voucher langsung tanpa login via QRIS / Payment Gateway</p>
    </div>
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <a href="{{ route('public.voucher.index') }}" target="_blank" class="btn btn-primary">
            🌐 Buka Halaman Beli Publik
        </a>
        <a href="{{ route('payment-gateway.settings') }}" class="btn btn-outline">
            ⚙️ Pengaturan Payment Gateway
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success mb-20" style="background:rgba(0,230,118,0.1);border:1px solid rgba(0,230,118,0.3);color:#00e676;padding:12px 16px;border-radius:10px;">
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="alert alert-danger mb-20" style="background:rgba(255,68,68,0.1);border:1px solid rgba(255,68,68,0.3);color:#ff4444;padding:12px 16px;border-radius:10px;">
    {{ session('error') }}
</div>
@endif

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px;">
    <div class="card" style="padding:20px;border-left:3px solid #25d366;">
        <div style="font-size:13px;color:var(--text-muted);margin-bottom:6px;">Order Berhasil (Lunas)</div>
        <div style="font-size:28px;font-weight:700;color:#25d366;">{{ number_format($stats['paid_count']) }}</div>
    </div>
    <div class="card" style="padding:20px;border-left:3px solid #00d4ff;">
        <div style="font-size:13px;color:var(--text-muted);margin-bottom:6px;">Total Pendapatan Langsung</div>
        <div style="font-size:24px;font-weight:700;color:#00d4ff;">Rp {{ number_format($stats['total_revenue'], 0, ',', '.') }}</div>
    </div>
    <div class="card" style="padding:20px;border-left:3px solid #f59e0b;">
        <div style="font-size:13px;color:var(--text-muted);margin-bottom:6px;">Menunggu Pembayaran</div>
        <div style="font-size:28px;font-weight:700;color:#f59e0b;">{{ number_format($stats['pending_count']) }}</div>
    </div>
    <div class="card" style="padding:20px;border-left:3px solid #ef4444;">
        <div style="font-size:13px;color:var(--text-muted);margin-bottom:6px;">Lunas (Menunggu Stok)</div>
        <div style="font-size:28px;font-weight:700;color:#ef4444;">{{ number_format($stats['out_of_stock_count']) }}</div>
    </div>
</div>

<div class="card mb-20" style="padding:16px 20px;">
    <form action="{{ route('public-orders.index') }}" method="GET" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
        <div style="flex:1;min-width:220px;">
            <input type="text" name="search" class="form-control" placeholder="Cari kode ref, nama pembeli, atau no. WA..." value="{{ request('search') }}">
        </div>
        <div style="min-width:160px;">
            <select name="status" class="form-control" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Lunas</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="paid_out_of_stock" {{ request('status') === 'paid_out_of_stock' ? 'selected' : '' }}>Lunas - Stok Habis</option>
                <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
            </select>
        </div>
        <button type="submit" class="btn btn-outline">Filter</button>
    </form>
</div>

<div class="card" style="padding:0;overflow:hidden;">
    <div style="overflow-x:auto;">
        <table class="table" style="width:100%;border-collapse:collapse;">
            <thead>
                <tr>
                    <th style="padding:14px 16px;text-align:left;">Ref Order</th>
                    <th style="padding:14px 16px;text-align:left;">Pembeli</th>
                    <th style="padding:14px 16px;text-align:left;">Paket & Gateway</th>
                    <th style="padding:14px 16px;text-align:left;">Kode Voucher</th>
                    <th style="padding:14px 16px;text-align:right;">Nominal</th>
                    <th style="padding:14px 16px;text-align:center;">Status</th>
                    <th style="padding:14px 16px;text-align:right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr style="border-top:1px solid var(--border-color);">
                    <td style="padding:14px 16px;">
                        <a href="{{ route('public.voucher.status', $order->reference_code) }}" target="_blank" style="font-family:monospace;font-weight:700;color:#00d4ff;text-decoration:none;">
                            {{ $order->reference_code }}
                        </a>
                        <div style="font-size:11px;color:var(--text-muted);">{{ $order->created_at->format('d M Y H:i') }}</div>
                    </td>
                    <td style="padding:14px 16px;">
                        <div style="font-weight:600;">{{ $order->buyer_name }}</div>
                        <div style="font-size:12px;color:var(--text-muted);">{{ $order->buyer_phone ?: 'Tanpa No. WA' }}</div>
                    </td>
                    <td style="padding:14px 16px;">
                        <div>{{ $order->package?->name ?? '-' }}</div>
                        <div style="font-size:11px;color:var(--text-muted);">Via {{ strtoupper($order->gateway) }}</div>
                    </td>
                    <td style="padding:14px 16px;">
                        @if($order->voucher)
                            <code style="background:rgba(37,211,102,0.12);color:#4ade80;padding:4px 8px;border-radius:6px;font-weight:700;">{{ $order->voucher->code }}</code>
                        @else
                            <span style="color:var(--text-muted);">-</span>
                        @endif
                    </td>
                    <td style="padding:14px 16px;text-align:right;font-weight:700;">
                        Rp {{ number_format((float)$order->amount, 0, ',', '.') }}
                    </td>
                    <td style="padding:14px 16px;text-align:center;">
                        <span style="font-size:12px;font-weight:700;">{{ strtoupper($order->status) }}</span>
                    </td>
                    <td style="padding:14px 16px;text-align:right;">
                        @if($order->status === 'paid_out_of_stock')
                        <form action="{{ route('public-orders.retry-fulfill', $order) }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-primary">Alokasikan Stok</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="padding:36px;text-align:center;color:var(--text-muted);">Belum ada order pembelian voucher publik.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($orders->hasPages())
    <div style="padding:16px 20px;">{{ $orders->links() }}</div>
    @endif
</div>
@endsection

