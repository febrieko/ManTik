@extends('layouts.app')

@section('title', 'Invoice ' . $billing->invoice_number)
@section('page-title', 'Detail Invoice & Pembayaran')

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
    <div class="page-header-title">
        <h2 style="font-family:monospace;">{{ $billing->invoice_number }}</h2>
        <p>Detail tagihan layanan pelanggan dan pencatatan pembayaran</p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <a href="{{ route('billing.index') }}" class="btn btn-outline">← Daftar Tagihan</a>
        @if($billing->status !== 'paid' && $billing->customer?->phone)
        <form action="{{ route('billing.remind', $billing) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-outline" style="border-color:rgba(37,211,102,0.45);color:#25d366;">
                🔔 Kirim Pengingat WA
            </button>
        </form>
        @endif
        <button type="button" onclick="window.print()" class="btn btn-outline">🖨️ Cetak</button>
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

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:20px;align-items:start;">
    {{-- Kartu Invoice --}}
    <div class="card" style="padding:24px;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;border-bottom:1px solid var(--border-color);padding-bottom:16px;margin-bottom:18px;">
            <div>
                <div style="font-size:12px;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;">INVOICE TAGIHAN</div>
                <div style="font-size:22px;font-weight:700;font-family:monospace;color:#00d4ff;">{{ $billing->invoice_number }}</div>
                <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">Dibuat: {{ $billing->created_at->format('d M Y H:i') }}</div>
            </div>
            @php
                $badgeStyle = match($billing->status) {
                    'paid' => 'background:rgba(37,211,102,0.15);color:#25d366;border:1px solid rgba(37,211,102,0.35);',
                    'partial' => 'background:rgba(0,212,255,0.15);color:#00d4ff;border:1px solid rgba(0,212,255,0.35);',
                    'overdue' => 'background:rgba(239,68,68,0.15);color:#ef4444;border:1px solid rgba(239,68,68,0.35);',
                    default => 'background:rgba(245,158,11,0.15);color:#f59e0b;border:1px solid rgba(245,158,11,0.35);',
                };
            @endphp
            <span style="padding:6px 14px;border-radius:999px;font-size:13px;font-weight:700;{{ $badgeStyle }}">
                {{ strtoupper($billing->status) }}
            </span>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px;">
            <div>
                <div style="font-size:12px;color:var(--text-muted);">Pelanggan</div>
                @if($billing->customer)
                    <div style="font-weight:700;font-size:15px;">
                        <a href="{{ route('customers.show', $billing->customer) }}" style="color:var(--text-primary);text-decoration:none;">
                            {{ $billing->customer->name }}
                        </a>
                    </div>
                    <div style="font-size:13px;color:var(--text-muted);">{{ $billing->customer->phone ?? '-' }}</div>
                    <div style="font-size:12px;color:#f59e0b;margin-top:3px;">
                        Saldo Deposit: Rp {{ number_format((float)$billing->customer->balance, 0, ',', '.') }}
                    </div>
                @else
                    <div>-</div>
                @endif
            </div>
            <div>
                <div style="font-size:12px;color:var(--text-muted);">Periode & Jatuh Tempo</div>
                <div style="font-weight:600;">{{ $billing->period_start->format('d M Y') }} – {{ $billing->period_end->format('d M Y') }}</div>
                <div style="font-size:13px;color:{{ $billing->isOverdue() ? '#ef4444' : 'var(--text-muted)' }};margin-top:3px;">
                    Jatuh Tempo: {{ $billing->due_date->format('d M Y') }}
                </div>
            </div>
        </div>

        <div style="background:rgba(255,255,255,0.03);border:1px solid var(--border-color);border-radius:10px;padding:16px;margin-bottom:16px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                <span style="color:var(--text-muted);">Paket / Deskripsi</span>
                <span style="font-weight:600;">{{ $billing->package->name ?? 'Tagihan Layanan Internet' }}</span>
            </div>
            <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                <span style="color:var(--text-muted);">Total Tagihan</span>
                <span style="font-weight:600;">Rp {{ number_format((float)$billing->amount, 0, ',', '.') }}</span>
            </div>
            <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                <span style="color:var(--text-muted);">Sudah Dibayar</span>
                <span style="font-weight:600;color:#25d366;">Rp {{ number_format((float)$billing->paid_amount, 0, ',', '.') }}</span>
            </div>
            <div style="display:flex;justify-content:space-between;padding-top:10px;border-top:1px solid var(--border-color);font-size:16px;">
                <span style="font-weight:700;">Sisa Tagihan</span>
                <span style="font-weight:700;color:{{ $billing->remaining_amount > 0 ? '#f59e0b' : '#25d366' }};">
                    Rp {{ number_format($billing->remaining_amount, 0, ',', '.') }}
                </span>
            </div>
        </div>

        @if($billing->paid_at)
        <div style="font-size:13px;color:#25d366;margin-bottom:10px;">
            ✅ Lunas pada {{ $billing->paid_at->format('d M Y H:i') }} ({{ strtoupper($billing->payment_method ?? '-') }})
        </div>
        @endif

        @if($billing->notes)
        <div style="font-size:13px;color:var(--text-muted);white-space:pre-line;background:rgba(255,255,255,0.02);padding:10px 12px;border-radius:8px;">
            <strong style="color:var(--text-primary);">Catatan:</strong><br>{{ $billing->notes }}
        </div>
        @endif

        @if((float)$billing->paid_amount <= 0 && $billing->status !== 'paid')
        <form action="{{ route('billing.destroy', $billing) }}" method="POST" style="margin-top:16px;"
              onsubmit="return confirm('Hapus tagihan {{ $billing->invoice_number }}?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline" style="color:#ef4444;border-color:rgba(239,68,68,0.4);">
                🗑️ Hapus Tagihan
            </button>
        </form>
        @endif
    </div>

    {{-- Panel Pembayaran --}}
    @if($billing->status !== 'paid' && $billing->status !== 'cancelled')
    <div class="card" style="padding:24px;">
        <h3 style="margin-top:0;margin-bottom:16px;font-size:17px;">💳 Catat Pembayaran Tagihan</h3>

        <form action="{{ route('billing.pay', $billing) }}" method="POST">
            @csrf

            <div class="form-group mb-16">
                <label class="form-label">Metode Pembayaran <span style="color:#ef4444;">*</span></label>
                <select name="payment_method" class="form-control" required>
                    <option value="cash">Tunai / Cash</option>
                    <option value="transfer">Transfer Bank / QRIS Manual</option>
                    @if($billing->customer)
                        <option value="balance" {{ (float)$billing->customer->balance >= $billing->remaining_amount ? 'selected' : '' }}>
                            Potong Saldo Deposit (Tersedia: Rp {{ number_format((float)$billing->customer->balance, 0, ',', '.') }})
                        </option>
                    @endif
                </select>
            </div>

            <div class="form-group mb-16">
                <label class="form-label">Nominal Pembayaran (Rp) <span style="color:#ef4444;">*</span></label>
                <input type="number" name="amount" class="form-control" min="1" max="{{ (float)$billing->remaining_amount }}" step="1"
                       value="{{ (int)$billing->remaining_amount }}" required>
                <small style="color:var(--text-muted);">Maksimal sesuai sisa tagihan: Rp {{ number_format($billing->remaining_amount, 0, ',', '.') }}</small>
            </div>

            <div class="form-group mb-20">
                <label class="form-label">Catatan Pembayaran</label>
                <input type="text" name="notes" class="form-control" placeholder="Contoh: Diterima oleh kasir / potong deposit">
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;">
                ✅ Proses Pembayaran & Kirim Struk WA
            </button>
        </form>
    </div>
    @else
    <div class="card" style="padding:24px;text-align:center;">
        <div style="font-size:40px;margin-bottom:8px;">✅</div>
        <h3 style="margin:0 0 6px;">Tagihan Sudah Selesai</h3>
        <p style="color:var(--text-muted);margin:0;">Status invoice ini adalah <strong>{{ strtoupper($billing->status) }}</strong>.</p>
    </div>
    @endif
</div>
@endsection

