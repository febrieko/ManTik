@extends('layouts.app')

@section('title', 'Edit User PPPoE')
@section('page-title', 'Edit User PPPoE')

@section('content')
<div class="page-header">
    <div class="page-header-title">
        <h2>Edit User: {{ $pppoe->username }}</h2>
        <p>Perbarui data dan konfigurasi PPPoE</p>
    </div>
    <a href="{{ route('pppoe.index') }}" class="btn btn-outline">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Kembali
    </a>
</div>

<div class="grid" style="grid-template-columns: 2fr 1fr; gap: 24px;">
    <div class="card">
        <form action="{{ route('pppoe.update', $pppoe) }}" method="POST">
            @csrf @method('PUT')
            
            <div class="card-header">
                <div class="card-title">Kredensial & Autentikasi</div>
            </div>

            <div class="form-group">
                <label class="form-label">Username (Secret)</label>
                <input type="text" class="form-control" value="{{ $pppoe->username }}" disabled style="background: rgba(255,255,255,0.02); color: #8892b0;">
                <div class="text-sm text-muted mt-1">Username tidak dapat diubah setelah dibuat.</div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password Baru</label>
                <input type="text" name="password" id="password" class="form-control" placeholder="Kosongkan jika tidak ingin mengubah password">
                @error('password') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="card-header" style="margin-top: 24px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 24px;">
                <div class="card-title">Profile & Konfigurasi Jaringan</div>
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label" for="customer_id">Pelanggan</label>
                    <select name="customer_id" id="customer_id" class="form-control">
                        <option value="">Tidak ditautkan</option>
                        @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" {{ old('customer_id', $pppoe->customer_id) == $customer->id ? 'selected' : '' }}>
                            {{ $customer->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="package_id">Paket / Profile</label>
                    <select name="package_id" id="package_id" class="form-control">
                        <option value="">Gunakan Default Profile Router</option>
                        @foreach($packages as $package)
                        <option value="{{ $package->id }}" {{ old('package_id', $pppoe->package_id) == $package->id ? 'selected' : '' }}>
                            {{ $package->name }} ({{ $package->bandwidth_limit }})
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label" for="local_address">Local Address</label>
                    <input type="text" name="local_address" id="local_address" class="form-control font-mono" value="{{ old('local_address', $pppoe->local_address) }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="remote_address">Remote Address</label>
                    <input type="text" name="remote_address" id="remote_address" class="form-control font-mono" value="{{ old('remote_address', $pppoe->remote_address) }}">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="comment">Komentar / Catatan</label>
                <input type="text" name="comment" id="comment" class="form-control" value="{{ old('comment', $pppoe->comment) }}">
            </div>

            <div style="margin-top: 24px;">
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v14a2 2 0 01-2 2z"/></svg>
                    Update & Sync ke MikroTik
                </button>
            </div>
        </form>
    </div>
    
    <div>
        <div class="card mb-24">
            <div class="card-title" style="margin-bottom: 16px;">Status Sinkronisasi</div>
            @if($pppoe->mikrotik_id)
            <div class="d-flex align-center gap-8 mb-12">
                <div style="width:10px;height:10px;border-radius:50%;background:#00e676;box-shadow:0 0 8px rgba(0,230,118,0.5);"></div>
                <span class="font-bold">Tersinkronisasi</span>
            </div>
            <div class="text-sm font-mono text-muted mb-4">ID: {{ $pppoe->mikrotik_id }}</div>
            <div class="text-sm font-mono text-muted">Router: {{ $pppoe->router->name }}</div>
            @else
            <div class="d-flex align-center gap-8 mb-12">
                <div style="width:10px;height:10px;border-radius:50%;background:#ff4444;"></div>
                <span class="font-bold" style="color: #ff4444;">Tidak Sinkron</span>
            </div>
            <p class="text-sm text-muted">User ini tidak tertaut dengan ID Secret di MikroTik. Simpan form ini untuk mencoba sinkronisasi ulang.</p>
            @endif
        </div>
        
        <div class="card">
            <div class="card-title" style="margin-bottom: 16px; color: #ff4444;">Danger Zone</div>
            <form action="{{ route('pppoe.destroy', $pppoe) }}" method="POST" onsubmit="return confirm('Hapus user PPPoE ini secara permanen dari database dan router?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-outline w-100" style="color: #ff4444; border-color: rgba(255,68,68,0.3);">Hapus User</button>
            </form>
        </div>
    </div>
</div>
@endsection
