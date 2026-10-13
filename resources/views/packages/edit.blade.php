@extends('layouts.app')

@section('title', 'Edit Paket Layanan')
@section('page-title', 'Edit Paket')

@section('content')
<div class="page-header">
    <div class="page-header-title">
        <h2>Edit Paket Layanan</h2>
        <p>Ubah konfigurasi paket: {{ $package->name }}</p>
    </div>
    <a href="{{ route('packages.index') }}" class="btn btn-outline">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Kembali
    </a>
</div>

<div class="grid" style="grid-template-columns: 2fr 1fr; gap: 24px;">
    <div class="card">
        <form action="{{ route('packages.update', $package) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="card-header">
                <div class="card-title">Informasi Dasar</div>
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label" for="name">Nama Paket (Profile) *</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $package->name) }}" required>
                    @error('name') <div class="form-error">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="type">Tipe Layanan *</label>
                    <select name="type" id="type" class="form-control" required>
                        <option value="pppoe" {{ old('type', $package->type) == 'pppoe' ? 'selected' : '' }}>PPPoE</option>
                        <option value="hotspot" {{ old('type', $package->type) == 'hotspot' ? 'selected' : '' }}>Hotspot</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label" for="price">Harga (Rp) *</label>
                    <input type="number" name="price" id="price" class="form-control" value="{{ old('price', (int)$package->price) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="duration_days">Durasi Aktif (Hari) *</label>
                    <input type="number" name="duration_days" id="duration_days" class="form-control" value="{{ old('duration_days', $package->duration_days) }}" required>
                </div>
            </div>

            <div class="card-header" style="margin-top: 24px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 24px;">
                <div class="card-title">Konfigurasi Bandwidth</div>
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label" for="bandwidth_up">Bandwidth Upload (Kbps/Mbps)</label>
                    <input type="text" name="bandwidth_up" id="bandwidth_up" class="form-control" value="{{ old('bandwidth_up', $package->bandwidth_up) }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="bandwidth_down">Bandwidth Download (Kbps/Mbps)</label>
                    <input type="text" name="bandwidth_down" id="bandwidth_down" class="form-control" value="{{ old('bandwidth_down', $package->bandwidth_down) }}">
                </div>
            </div>

            <div class="form-group" style="margin-top: 16px;">
                <label class="form-label" for="description">Deskripsi</label>
                <textarea name="description" id="description" class="form-control" rows="3">{{ old('description', $package->description) }}</textarea>
            </div>

            <div class="form-group">
                <input type="hidden" name="is_active" value="0">
                <label class="form-checkbox">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $package->is_active) ? 'checked' : '' }}>
                    <span class="checkmark"></span>
                    <span class="label-text">Aktifkan Paket Ini</span>
                </label>
            </div>

            <div style="margin-top: 24px;">
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v14a2 2 0 01-2 2z"/></svg>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

    <div class="card" style="height: fit-content;">
        <div class="card-title" style="margin-bottom: 16px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; color: #00d4ff;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            Informasi
        </div>
        <p style="font-size: 13px; color: #8892b0; line-height: 1.6;">
            Mengubah nama paket akan mempengaruhi URL slug. Pastikan perubahan ini sejalan dengan nama profile yang ada di dalam Router MikroTik Anda untuk menghindari gagal sinkronisasi saat penambahan user.
        </p>
    </div>
</div>
@endsection
