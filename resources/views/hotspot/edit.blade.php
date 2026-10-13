@extends('layouts.app')

@section('title', 'Edit User Hotspot')
@section('page-title', 'Edit User Hotspot')

@section('content')
<div class="page-header">
    <div class="page-header-title">
        <h2>Edit User: {{ $hotspot->username }}</h2>
        <p>Perbarui data dan konfigurasi Hotspot</p>
    </div>
    <a href="{{ route('hotspot.index') }}" class="btn btn-outline">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Kembali
    </a>
</div>

<div class="grid" style="grid-template-columns: 2fr 1fr; gap: 24px;">
    <div class="card">
        <form action="{{ route('hotspot.update', $hotspot) }}" method="POST">
            @csrf @method('PUT')
            
            <div class="card-header">
                <div class="card-title">Kredensial & Autentikasi</div>
            </div>

            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" value="{{ $hotspot->username }}" disabled style="background: rgba(255,255,255,0.02); color: #8892b0;">
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

            <div class="form-group">
                <label class="form-label" for="package_id">Paket / Profile</label>
                <select name="package_id" id="package_id" class="form-control">
                    <option value="">Gunakan Default Profile Router</option>
                    @foreach($packages as $package)
                    <option value="{{ $package->id }}" {{ old('package_id', $hotspot->package_id) == $package->id ? 'selected' : '' }}>
                        {{ $package->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label" for="limit_uptime">Time Limit (Uptime)</label>
                    <input type="text" name="limit_uptime" id="limit_uptime" class="form-control" value="{{ old('limit_uptime', $hotspot->limit_uptime) }}" placeholder="e.g. 30d, 12h">
                </div>
                <div class="form-group">
                    <label class="form-label" for="limit_bytes_total">Data Limit (Bytes)</label>
                    <input type="number" name="limit_bytes_total" id="limit_bytes_total" class="form-control" value="{{ old('limit_bytes_total', $hotspot->limit_bytes_total) }}" placeholder="e.g. 1073741824">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="comment">Komentar / Catatan</label>
                <input type="text" name="comment" id="comment" class="form-control" value="{{ old('comment', $hotspot->comment) }}">
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
            <div class="card-title" style="margin-bottom: 16px;">Info Router</div>
            <div class="d-flex align-center gap-8 mb-12">
                <div style="width:10px;height:10px;border-radius:50%;background:#00e676;box-shadow:0 0 8px rgba(0,230,118,0.5);"></div>
                <span class="font-bold">Connected</span>
            </div>
            <div class="text-sm font-mono text-muted mb-4">Router: {{ $hotspot->router->name }}</div>
            <div class="text-sm font-mono text-muted">Profile aktif: {{ $hotspot->profile ?? 'default' }}</div>
        </div>
        
        <div class="card">
            <div class="card-title" style="margin-bottom: 16px; color: #ff4444;">Danger Zone</div>
            <form action="{{ route('hotspot.destroy', $hotspot) }}" method="POST" onsubmit="return confirm('Hapus user Hotspot ini secara permanen dari database dan router?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-outline w-100" style="color: #ff4444; border-color: rgba(255,68,68,0.3);">Hapus User</button>
            </form>
        </div>
    </div>
</div>
@endsection
