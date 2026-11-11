@extends('layouts.app')

@section('title', 'Buat Tagihan Baru')
@section('page-title', 'Buat Tagihan Manual')

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
    <div class="page-header-title">
        <h2>Buat Invoice Tagihan Baru</h2>
        <p>Tambahkan tagihan bulanan atau tagihan khusus untuk pelanggan</p>
    </div>
    <a href="{{ route('billing.index') }}" class="btn btn-outline">← Kembali ke Daftar Tagihan</a>
</div>

@if($errors->any())
<div class="alert alert-danger mb-20" style="background:rgba(255,68,68,0.1);border:1px solid rgba(255,68,68,0.3);color:#ff4444;padding:12px 16px;border-radius:10px;">
    <ul style="margin:0;padding-left:18px;">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="card" style="max-width:720px;padding:24px;">
    <form action="{{ route('billing.store') }}" method="POST">
        @csrf

        <div class="form-group mb-16">
            <label class="form-label">Pelanggan <span style="color:#ef4444;">*</span></label>
            <select name="customer_id" class="form-control" required>
                <option value="">-- Pilih Pelanggan --</option>
                @foreach($customers as $c)
                    <option value="{{ $c->id }}" {{ old('customer_id', $selectedCustomerId) == $c->id ? 'selected' : '' }}>
                        {{ $c->name }} ({{ $c->phone ?? 'Tanpa WA' }}) — Saldo: Rp {{ number_format((float)$c->balance, 0, ',', '.') }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group mb-16">
            <label class="form-label">Paket Layanan (Opsional)</label>
            <select name="package_id" id="packageSelect" class="form-control" onchange="applyPackagePrice()">
                <option value="">-- Tagihan Kustom / Tanpa Paket --</option>
                @foreach($packages as $pkg)
                    <option value="{{ $pkg->id }}" data-price="{{ (int)$pkg->price }}" {{ old('package_id') == $pkg->id ? 'selected' : '' }}>
                        {{ $pkg->name }} — Rp {{ number_format((float)$pkg->price, 0, ',', '.') }} ({{ strtoupper($pkg->type ?? 'pppoe') }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group mb-16">
            <label class="form-label">Nominal Tagihan (Rp) <span style="color:#ef4444;">*</span></label>
            <input type="number" name="amount" id="amountInput" class="form-control" min="1000" step="500" value="{{ old('amount', 150000) }}" required>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;" class="mb-16">
            <div class="form-group">
                <label class="form-label">Awal Periode <span style="color:#ef4444;">*</span></label>
                <input type="date" name="period_start" class="form-control" value="{{ old('period_start', now()->startOfMonth()->toDateString()) }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Akhir Periode <span style="color:#ef4444;">*</span></label>
                <input type="date" name="period_end" class="form-control" value="{{ old('period_end', now()->endOfMonth()->toDateString()) }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Jatuh Tempo <span style="color:#ef4444;">*</span></label>
                <input type="date" name="due_date" class="form-control" value="{{ old('due_date', now()->startOfMonth()->addDays(9)->toDateString()) }}" required>
            </div>
        </div>

        <div class="form-group mb-24">
            <label class="form-label">Catatan Tagihan</label>
            <textarea name="notes" class="form-control" rows="3" placeholder="Contoh: Tagihan internet bulanan periode {{ now()->translatedFormat('F Y') }}">{{ old('notes') }}</textarea>
        </div>

        <div style="display:flex;gap:12px;justify-content:flex-end;">
            <a href="{{ route('billing.index') }}" class="btn btn-outline">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan & Lihat Invoice</button>
        </div>
    </form>
</div>

<script>
function applyPackagePrice() {
    const sel = document.getElementById('packageSelect');
    const opt = sel.options[sel.selectedIndex];
    const price = opt ? opt.getAttribute('data-price') : null;
    if (price) {
        document.getElementById('amountInput').value = price;
    }
}
</script>
@endsection

