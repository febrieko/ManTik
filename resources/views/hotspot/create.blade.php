@extends('layouts.app')

@section('title', 'Tambah User Hotspot')
@section('page-title', 'Tambah User Hotspot')

@section('content')
<div class="page-header">
    <div class="page-header-title">
        <h2>Tambah User Hotspot Manual</h2>
        <p>Buat akun Hotspot satu per satu untuk pelanggan reguler</p>
    </div>
    <a href="{{ route('hotspot.index') }}" class="btn btn-outline">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Kembali
    </a>
</div>

<div class="grid" style="grid-template-columns: 2fr 1fr; gap: 24px;">
    <div class="card">
        <form action="{{ route('hotspot.store') }}" method="POST">
            @csrf
            
            <div class="card-header">
                <div class="card-title">Kredensial & Autentikasi</div>
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label" for="router_id">Router *</label>
                    <select name="router_id" id="router_id" class="form-control" required>
                        <option value="">Pilih Router</option>
                        @foreach($routers as $router)
                        <option value="{{ $router->id }}" {{ old('router_id') == $router->id ? 'selected' : '' }}>
                            {{ $router->name }} ({{ $router->host }})
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="package_id">Paket / Profile</label>
                    <select name="package_id" id="package_id" class="form-control">
                        <option value="">Default Profile</option>
                        @foreach($packages as $package)
                        <option value="{{ $package->id }}" {{ old('package_id') == $package->id ? 'selected' : '' }}>
                            {{ $package->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label" for="username">Username *</label>
                    <input type="text" name="username" id="username" class="form-control" value="{{ old('username') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="password">Password *</label>
                    <input type="text" name="password" id="password" class="form-control" value="{{ old('password') }}" required>
                </div>
            </div>

            <div class="card-header" style="margin-top: 24px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 24px;">
                <div class="card-title">Limitasi Spesifik (Opsional)</div>
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label" for="limit_uptime">Time Limit (Uptime)</label>
                    <input type="text" name="limit_uptime" id="limit_uptime" class="form-control" value="{{ old('limit_uptime') }}" placeholder="e.g. 30d, 12h">
                </div>
                <div class="form-group">
                    <label class="form-label" for="limit_bytes_total">Data Limit (Bytes)</label>
                    <input type="number" name="limit_bytes_total" id="limit_bytes_total" class="form-control" value="{{ old('limit_bytes_total') }}" placeholder="e.g. 1073741824">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="comment">Komentar / Catatan</label>
                <input type="text" name="comment" id="comment" class="form-control" value="{{ old('comment') }}">
            </div>

            <div style="margin-top: 24px;">
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v14a2 2 0 01-2 2z"/></svg>
                    Simpan & Sync ke MikroTik
                </button>
            </div>
        </form>
    </div>

    <div class="card" style="height: fit-content;">
        <div class="card-title" style="margin-bottom: 16px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; color: #00d4ff;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            Mode Input
        </div>
        <p style="font-size: 13px; color: #8892b0; line-height: 1.6; margin-bottom: 12px;">
            Halaman ini digunakan untuk membuat akun hotspot individual. Jika Anda ingin membuat voucher secara massal, silakan gunakan menu <strong>Generate Voucher</strong>.
        </p>
    </div>
</div>
@endsection
