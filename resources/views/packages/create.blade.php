@extends('layouts.app')

@section('title', 'Tambah Paket Layanan')
@section('page-title', 'Tambah Paket')

@section('content')
<div class="page-header">
    <div class="page-header-title">
        <h2>Tambah Paket Layanan</h2>
        <p>Buat paket / profile baru untuk PPPoE atau Hotspot</p>
    </div>
    <a href="{{ route('packages.index') }}" class="btn btn-outline">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Kembali
    </a>
</div>

<div class="grid" style="grid-template-columns: 2fr 1fr; gap: 24px;">
    <div class="card">
        <form action="{{ route('packages.store') }}" method="POST">
            @csrf
            
            <div class="card-header">
                <div class="card-title">Informasi Dasar</div>
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label" for="name">Nama Paket (Profile) *</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
                    @error('name') <div class="form-error">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="type">Tipe Layanan *</label>
                    <select name="type" id="type" class="form-control" required>
                        <option value="pppoe" {{ old('type') == 'pppoe' ? 'selected' : '' }}>PPPoE</option>
                        <option value="hotspot" {{ old('type') == 'hotspot' ? 'selected' : '' }}>Hotspot</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label" for="price">Harga (Rp) *</label>
                    <input type="number" name="price" id="price" class="form-control" value="{{ old('price', 0) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="duration_days">Durasi Aktif (Hari) *</label>
                    <input type="number" name="duration_days" id="duration_days" class="form-control" value="{{ old('duration_days', 30) }}" required>
                </div>
            </div>

            <div class="card-header" style="margin-top: 24px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 24px;">
                <div class="card-title">Konfigurasi Bandwidth</div>
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label" for="bandwidth_up">Bandwidth Upload (Kbps/Mbps)</label>
                    <input type="text" name="bandwidth_up" id="bandwidth_up" class="form-control" value="{{ old('bandwidth_up') }}" placeholder="Contoh: 2M atau 512K">
                </div>
                <div class="form-group">
                    <label class="form-label" for="bandwidth_down">Bandwidth Download (Kbps/Mbps)</label>
                    <input type="text" name="bandwidth_down" id="bandwidth_down" class="form-control" value="{{ old('bandwidth_down') }}" placeholder="Contoh: 10M atau 2M">
                </div>
            </div>

            <div class="form-group" style="margin-top: 16px;">
                <label class="form-label" for="description">Deskripsi</label>
                <textarea name="description" id="description" class="form-control" rows="3">{{ old('description') }}</textarea>
            </div>

            <div class="form-group">
                <label class="form-checkbox">
                    <input type="checkbox" name="is_active" value="1" checked>
                    <span class="checkmark"></span>
                    <span class="label-text">Aktifkan Paket Ini</span>
                </label>
            </div>

            <div style="margin-top: 24px;">
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v14a2 2 0 01-2 2z"/></svg>
                    Simpan Paket
                </button>
            </div>
        </form>
    </div>

    <div class="card" style="height: fit-content;">
        <div class="card-title" style="margin-bottom: 16px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; color: #00d4ff;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            Panduan
        </div>
        <p style="font-size: 13px; color: #8892b0; line-height: 1.6; margin-bottom: 12px;">
            Nama Paket ini harus sesuai (identik) dengan nama Profile yang ada di MikroTik Anda.
        </p>
        <p style="font-size: 13px; color: #8892b0; line-height: 1.6;">
            Disarankan untuk menggunakan fitur <strong>Sync dari MikroTik</strong> pada halaman daftar paket agar otomatis menarik daftar Profile yang sudah ada.
        </p>
    </div>
</div>
@endsection
