@extends('layouts.app')

@section('title', 'Tambah Router')
@section('page-title', 'Tambah Router Baru')

@section('content')
<div class="page-header">
    <div class="page-header-title">
        <h2>Tambah Router Baru</h2>
        <p>Daftarkan perangkat MikroTik ke dalam sistem MANTIK</p>
    </div>
    <a href="{{ route('routers.index') }}" class="btn btn-outline">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Kembali
    </a>
</div>

<div class="grid" style="grid-template-columns: 1.5fr 1fr; gap: 20px;">
    {{-- Form --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12H3a9 9 0 0018 0h-2"/></svg>
                Informasi Router
            </div>
        </div>

        <form action="{{ route('routers.store') }}" method="POST" id="routerForm">
            @csrf

            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label" for="name">Nama Router *</label>
                    <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}"
                           placeholder="e.g. Router Utama" required>
                    @error('name') <div class="form-error">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="host">IP Address / Host *</label>
                    <input type="text" id="host" name="host" class="form-control" value="{{ old('host') }}"
                           placeholder="192.168.1.1" required>
                    @error('host') <div class="form-error">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label" for="username">Username *</label>
                    <input type="text" id="username" name="username" class="form-control" value="{{ old('username', 'admin') }}"
                           placeholder="admin" required>
                    @error('username') <div class="form-error">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="port">Port API *</label>
                    <input type="number" id="port" name="port" class="form-control" value="{{ old('port', 8728) }}"
                           min="1" max="65535" required>
                    @error('port') <div class="form-error">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label" for="ros_version">Versi RouterOS *</label>
                    <select id="ros_version" name="ros_version" class="form-control" required>
                        <option value="v7" {{ old('ros_version', 'v7') == 'v7' ? 'selected' : '' }}>RouterOS v7</option>
                        <option value="v6" {{ old('ros_version', 'v7') == 'v6' ? 'selected' : '' }}>RouterOS v6</option>
                    </select>
                    @error('ros_version') <div class="form-error">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="location">Lokasi</label>
                    <input type="text" id="location" name="location" class="form-control" value="{{ old('location') }}"
                           placeholder="e.g. Ruang Server Lt. 2">
                    @error('location') <div class="form-error">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="ssid">Nama WiFi / SSID Hotspot</label>
                <input type="text" id="ssid" name="ssid" class="form-control" value="{{ old('ssid') }}"
                       placeholder="Contoh: Hotspot-Kafe, MANTIK-WiFi">
                @error('ssid') <div class="form-error">{{ $message }}</div> @enderror
                <small class="text-muted" style="font-size: 11px; margin-top: 4px; display: block;">Nama jaringan WiFi hotspot yang dipancarkan oleh router ini (disertakan pada pesan balasan voucher ke pelanggan)</small>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password *</label>
                <div style="position: relative;">
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="Password router" required>
                    <button type="button" onclick="togglePass()" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #4a5568; padding: 4px; display: flex; align-items: center;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
                @error('password') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label" for="brand">Brand</label>
                    <input type="text" id="brand" name="brand" class="form-control" value="{{ old('brand', 'MikroTik') }}"
                           placeholder="MikroTik">
                    @error('brand') <div class="form-error">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="model">Model Perangkat</label>
                    <input type="text" id="model" name="model" class="form-control" value="{{ old('model') }}"
                           placeholder="e.g. RB4011, CCR1009, hAP ac2">
                    @error('model') <div class="form-error">{{ $message }}</div> @enderror
                </div>
            </div>



            <div class="form-group">
                <label class="form-label" for="description">Deskripsi</label>
                <textarea id="description" name="description" class="form-control" rows="3"
                          placeholder="Deskripsi singkat tentang router ini...">{{ old('description') }}</textarea>
                @error('description') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div style="display: flex; gap: 12px; margin-top: 8px;">
                <button type="button" onclick="testConnection()" class="btn btn-outline" id="testBtn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1.42 9A16 16 0 0122.58 9M5 12.55a11 11 0 0114.08 0"/></svg>
                    Test Koneksi
                </button>
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v14a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Simpan Router
                </button>
            </div>
        </form>
    </div>

    {{-- Help Card --}}
    <div style="display: flex; flex-direction: column; gap: 16px;">
        {{-- Test Result --}}
        <div class="card" id="testResult" style="display: none;">
            <div class="card-title" style="margin-bottom: 12px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px; color: #00d4ff;"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                Hasil Test Koneksi
            </div>
            <div id="testResultContent"></div>
        </div>

        {{-- Info Card --}}
        <div class="card">
            <div class="card-title" style="margin-bottom: 16px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px; color: #00d4ff;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><circle cx="12" cy="16" r="0.5" fill="currentColor" stroke="none"/></svg>
                Panduan Konfigurasi
            </div>
            <div style="font-size: 13px; color: #8892b0; line-height: 1.8;">
                <p style="margin-bottom: 12px;">Pastikan RouterOS API sudah diaktifkan di MikroTik:</p>
                <div style="background: rgba(0,212,255,0.05); border: 1px solid rgba(0,212,255,0.15); border-radius: 8px; padding: 12px; font-family: monospace; font-size: 12px; color: #e8eaf6; margin-bottom: 12px; line-height: 2;">
                    /ip service<br>
                    set api disabled=no port=8728<br>
                    set api-ssl disabled=no port=8729
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <div style="display: flex; align-items: flex-start; gap: 8px;">
                        <span style="color: #00d4ff; font-weight: 700; flex-shrink: 0;">Port API:</span>
                        <span>8728 (tanpa SSL) atau 8729 (dengan SSL)</span>
                    </div>
                    <div style="display: flex; align-items: flex-start; gap: 8px;">
                        <span style="color: #00d4ff; font-weight: 700; flex-shrink: 0;">Firewall:</span>
                        <span>Pastikan port 8728 tidak diblokir firewall</span>
                    </div>
                    <div style="display: flex; align-items: flex-start; gap: 8px;">
                        <span style="color: #00d4ff; font-weight: 700; flex-shrink: 0;">User:</span>
                        <span>Gunakan user dengan full policy atau minimal read+write+api</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function togglePass() {
    const input = document.getElementById('password');
    input.type = input.type === 'password' ? 'text' : 'password';
}

function testConnection() {
    const host = document.getElementById('host').value;
    const port = document.getElementById('port').value;
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;

    if (!host || !username || !password) {
        alert('Isi Host, Username, dan Password terlebih dahulu!');
        return;
    }

    const btn = document.getElementById('testBtn');
    btn.innerHTML = '<div class="spinner" style="width:14px;height:14px;"></div> Testing...';
    btn.disabled = true;

    const result = document.getElementById('testResult');
    const content = document.getElementById('testResultContent');
    result.style.display = 'block';
    content.innerHTML = '<div style="text-align:center;padding:20px;"><div class="spinner" style="margin:0 auto;"></div><p style="margin-top:12px;color:#4a5568;font-size:13px;">Mencoba koneksi ke ' + host + ':' + port + '...</p></div>';

    // Simulate connection test (actual test happens on form submit)
    setTimeout(() => {
        btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1.42 9A16 16 0 0122.58 9M5 12.55a11 11 0 0114.08 0"/></svg> Test Koneksi';
        btn.disabled = false;
        content.innerHTML = `
            <div style="background: rgba(0,212,255,0.05); border-radius: 8px; padding: 12px; font-size: 13px;">
                <p style="color: #8892b0; margin-bottom: 8px;">Koneksi akan diuji saat Anda menyimpan router.</p>
                <p style="color: #4a5568; font-size: 12px;">Host: <strong style="color: #e8eaf6;">${host}:${port}</strong></p>
            </div>
        `;
    }, 1500);
}
</script>
@endpush
