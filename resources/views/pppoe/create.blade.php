@extends('layouts.app')

@section('title', 'Tambah User PPPoE')
@section('page-title', 'Tambah User PPPoE')

@section('content')
<div class="page-header">
    <div class="page-header-title">
        <h2>Tambah User PPPoE Baru</h2>
        <p>Buat akun dial-up PPPoE untuk pelanggan</p>
    </div>
    <a href="{{ route('pppoe.index') }}" class="btn btn-outline">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Kembali
    </a>
</div>

<div class="grid" style="grid-template-columns: 2fr 1fr; gap: 24px;">
    <div class="card">
        <form action="{{ route('pppoe.store') }}" method="POST">
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
                    @error('router_id') <div class="form-error">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="service">Service *</label>
                    <select name="service" id="service" class="form-control" required>
                        <option value="pppoe" {{ old('service') == 'pppoe' ? 'selected' : '' }}>PPPoE</option>
                        <option value="any" {{ old('service') == 'any' ? 'selected' : '' }}>Any</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label" for="username">Username (Secret) *</label>
                    <input type="text" name="username" id="username" class="form-control" value="{{ old('username') }}" required>
                    @error('username') <div class="form-error">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="password">Password *</label>
                    <input type="text" name="password" id="password" class="form-control" value="{{ old('password') }}" required>
                    @error('password') <div class="form-error">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="card-header" style="margin-top: 24px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 24px;">
                <div class="card-title">Profile & Konfigurasi Jaringan</div>
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label" for="customer_id">Pelanggan (Opsional)</label>
                    <select name="customer_id" id="customer_id" class="form-control">
                        <option value="">Tidak ditautkan</option>
                        @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                            {{ $customer->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="package_id">Paket / Profile (Opsional)</label>
                    <select name="package_id" id="package_id" class="form-control">
                        <option value="">Gunakan Default Profile Router</option>
                        @foreach($packages as $package)
                        <option value="{{ $package->id }}" {{ old('package_id') == $package->id ? 'selected' : '' }}>
                            {{ $package->name }} ({{ $package->bandwidth_limit }})
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label" for="local_address">Local Address (Opsional)</label>
                    <input type="text" name="local_address" id="local_address" class="form-control font-mono" value="{{ old('local_address') }}" placeholder="Contoh: 192.168.10.1">
                </div>
                <div class="form-group">
                    <label class="form-label" for="remote_address">Remote Address (Static IP)</label>
                    <input type="text" name="remote_address" id="remote_address" class="form-control font-mono" value="{{ old('remote_address') }}" placeholder="Contoh: 192.168.10.2">
                    <div style="font-size: 11px; color: #8892b0; margin-top: 4px;">Kosongkan jika menggunakan IP Pool pada profile</div>
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
            Informasi
        </div>
        <p style="font-size: 13px; color: #8892b0; line-height: 1.6; margin-bottom: 12px;">
            Sistem akan secara otomatis menghubungi Router yang dipilih dan membuat "PPP Secret" melalui API.
        </p>
        <ul style="font-size: 13px; color: #8892b0; line-height: 1.6; padding-left: 16px;">
            <li>Pastikan profile paket yang Anda pilih <strong>sudah ada</strong> di MikroTik. MANTIK akan mengirim slug paket sebagai nama profile.</li>
            <li>Jika gagal menghubungi MikroTik, data akan tetap disimpan di Database MANTIK.</li>
        </ul>
    </div>
</div>
@endsection
