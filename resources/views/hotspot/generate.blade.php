@extends('layouts.app')

@section('title', 'Generate Voucher')
@section('page-title', 'Generate Voucher')

@section('content')
<div class="page-header">
    <div class="page-header-title">
        <h2>Generate Voucher Hotspot</h2>
        <p>Buat kode voucher secara massal dan sinkronisasi ke MikroTik</p>
    </div>
    <a href="{{ route('hotspot.vouchers') }}" class="btn btn-outline">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Daftar Voucher
    </a>
</div>

<div class="grid" style="grid-template-columns: 2fr 1fr; gap: 24px;">
    <div class="card">
        <form action="{{ route('hotspot.vouchers.store') }}" method="POST">
            @csrf
            
            <div class="card-header">
                <div class="card-title">Konfigurasi Voucher</div>
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label" for="router_id">Router Tujuan *</label>
                    <select name="router_id" id="router_id" class="form-control" required>
                        <option value="">Pilih Router</option>
                        @foreach($routers as $router)
                        <option value="{{ $router->id }}">{{ $router->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="package_id">Paket / Profile *</label>
                    <select name="package_id" id="package_id" class="form-control" required>
                        <option value="">Pilih Paket Hotspot</option>
                        @foreach($packages as $package)
                        <option value="{{ $package->id }}">{{ $package->name }} (Rp {{ number_format($package->price, 0, ',', '.') }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label" for="qty">Jumlah Voucher *</label>
                    <input type="number" name="qty" id="qty" class="form-control" value="10" min="1" max="500" required>
                    <div class="text-sm text-muted mt-1">Maksimal 500 voucher per generate</div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="mode">Mode Login *</label>
                    <select name="mode" id="mode" class="form-control" required>
                        <option value="vc">Username = Password</option>
                        <option value="up">Username & Password Beda</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label" for="length">Panjang Karakter *</label>
                    <input type="number" name="length" id="length" class="form-control" value="6" min="4" max="12" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="prefix">Prefix (Opsional)</label>
                    <input type="text" name="prefix" id="prefix" class="form-control" placeholder="MNTK" maxlength="4">
                </div>
            </div>

            <div class="card-header" style="margin-top: 24px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 24px;">
                <div class="card-title">Limitasi Spesifik (Override Profile)</div>
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label" for="time_limit">Time Limit (Uptime)</label>
                    <input type="text" name="time_limit" id="time_limit" class="form-control" placeholder="Contoh: 1h, 1d, 30d">
                    <div class="text-sm text-muted mt-1">Biarkan kosong untuk ikut profile</div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="data_limit">Data Limit (Bytes)</label>
                    <input type="number" name="data_limit" id="data_limit" class="form-control" placeholder="Contoh: 1073741824 (1GB)">
                    <div class="text-sm text-muted mt-1">Biarkan kosong untuk ikut profile</div>
                </div>
            </div>

            <div style="margin-top: 24px;">
                <button type="submit" class="btn btn-primary" onclick="this.innerHTML='<div class=\'spinner\' style=\'width:14px;height:14px;\'></div> Memproses...'; this.style.opacity='0.8';">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    Generate & Sync Sekarang
                </button>
            </div>
        </form>
    </div>

    <div class="card" style="height: fit-content;">
        <div class="card-title" style="margin-bottom: 16px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; color: #00d4ff;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            Petunjuk
        </div>
        <p style="font-size: 13px; color: #8892b0; line-height: 1.6; margin-bottom: 12px;">
            Proses ini akan membuat user hotspot di router MikroTik dan menyimpannya di database MANTIK.
        </p>
        <div style="background: rgba(0,212,255,0.05); border: 1px solid rgba(0,212,255,0.1); padding: 12px; border-radius: 8px; margin-bottom: 16px;">
            <div style="font-size: 11px; font-weight: 700; color: #00d4ff; margin-bottom: 8px; text-transform: uppercase;">Contoh Format Code</div>
            <div style="font-family: monospace; font-size: 13px; color: #e8eaf6;">
                Tanpa Prefix: <span style="color: #00e676;">a8bx9y</span><br>
                Dengan Prefix (MNTK): <span style="color: #00e676;">mntka8bx</span>
            </div>
        </div>
    </div>
</div>
@endsection
