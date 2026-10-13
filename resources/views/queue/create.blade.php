@extends('layouts.app')

@section('title', 'Tambah Queue')
@section('page-title', 'Tambah Queue')

@section('content')
<div class="page-header">
    <div class="page-header-title">
        <h2>Buat Rule Bandwidth (Queue)</h2>
        <p>Tambahkan limitasi bandwidth untuk target IP/Subnet tertentu</p>
    </div>
    <a href="{{ route('queue.index') }}" class="btn btn-outline">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Kembali
    </a>
</div>

<div class="grid" style="grid-template-columns: 2fr 1fr; gap: 24px;">
    <div class="card">
        <form action="{{ route('queue.store') }}" method="POST">
            @csrf
            <input type="hidden" name="type" value="simple">
            
            <div class="form-group">
                <label class="form-label" for="router_id">Router *</label>
                <select name="router_id" id="router_id" class="form-control" required>
                    <option value="">Pilih Router</option>
                    @foreach($routers as $router)
                    <option value="{{ $router->id }}">{{ $router->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label" for="name">Nama Queue *</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" placeholder="Contoh: Limitasi Kantor" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="target">Target (IP / Subnet) *</label>
                    <input type="text" name="target" id="target" class="form-control font-mono" value="{{ old('target') }}" placeholder="192.168.1.0/24" required>
                </div>
            </div>

            <div class="card-header" style="margin-top: 24px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 24px;">
                <div class="card-title">Max Limit (Bandwidth Maksimal)</div>
            </div>
            
            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label" for="max_limit_rx" style="color: #00e676;">Download (RX) *</label>
                    <select name="max_limit_rx" id="max_limit_rx" class="form-control font-mono" required>
                        <option value="512k">512 kbps</option>
                        <option value="1M">1 Mbps</option>
                        <option value="2M">2 Mbps</option>
                        <option value="3M">3 Mbps</option>
                        <option value="5M">5 Mbps</option>
                        <option value="10M" selected>10 Mbps</option>
                        <option value="20M">20 Mbps</option>
                        <option value="50M">50 Mbps</option>
                        <option value="100M">100 Mbps</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="max_limit_tx" style="color: #00d4ff;">Upload (TX) *</label>
                    <select name="max_limit_tx" id="max_limit_tx" class="form-control font-mono" required>
                        <option value="512k">512 kbps</option>
                        <option value="1M">1 Mbps</option>
                        <option value="2M">2 Mbps</option>
                        <option value="3M">3 Mbps</option>
                        <option value="5M" selected>5 Mbps</option>
                        <option value="10M">10 Mbps</option>
                        <option value="20M">20 Mbps</option>
                        <option value="50M">50 Mbps</option>
                        <option value="100M">100 Mbps</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="comment">Komentar / Catatan</label>
                <input type="text" name="comment" id="comment" class="form-control" value="{{ old('comment') }}">
            </div>

            <div style="margin-top: 24px;">
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v14a2 2 0 01-2 2z"/></svg>
                    Simpan & Terapkan ke MikroTik
                </button>
            </div>
        </form>
    </div>

    <div class="card" style="height: fit-content;">
        <div class="card-title" style="margin-bottom: 16px;">Format Bandwidth</div>
        <p style="font-size: 13px; color: #8892b0; line-height: 1.6;">
            Format pengisian target bisa menggunakan single IP atau rentang Subnet jaringan.
        </p>
        <ul style="font-size: 13px; color: #8892b0; line-height: 1.6; padding-left: 16px;">
            <li><strong style="color:#e8eaf6;">Single IP:</strong> 192.168.1.10</li>
            <li><strong style="color:#e8eaf6;">Subnet:</strong> 192.168.1.0/24</li>
        </ul>
        <div style="margin-top: 16px; font-size: 11px; color: #ff4444; background: rgba(255,68,68,0.1); padding: 8px; border-radius: 6px;">
            Note: Urutan priority Queue di MikroTik ditentukan dari urutan list (dari atas ke bawah). MANTIK secara default menambahkan ke urutan paling bawah.
        </div>
    </div>
</div>
@endsection
