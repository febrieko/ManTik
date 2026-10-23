@extends('layouts.app')

@section('title', 'Pengaturan WA Gateway')
@section('page-title', 'Pengaturan WhatsApp Gateway')

@section('content')
<div class="page-header">
    <div class="page-header-title">
        <h2 style="display:flex;align-items:center;gap:10px;">
            <span style="display:inline-flex;width:36px;height:36px;background:linear-gradient(135deg,#25d366,#128C7E);border-radius:10px;align-items:center;justify-content:center;">
                <svg viewBox="0 0 24 24" fill="white" width="20" height="20"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
            </span>
            Pengaturan WhatsApp Gateway
        </h2>
        <p>Konfigurasi integrasi WhatsApp Gateway untuk sistem voucher otomatis</p>
    </div>
    <a href="{{ route('whatsapp.voucher-requests') }}" class="btn btn-secondary">
        ← Riwayat Request
    </a>
</div>

@if(session('success'))
<div class="alert alert-success mb-24">✅ {{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger mb-24">❌ {{ session('error') }}</div>
@endif
@if ($errors->any())
<div class="alert alert-danger mb-24">
    <strong>⚠️ Gagal menyimpan pengaturan:</strong>
    <ul style="margin:8px 0 0 20px;padding:0;">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start;">

    {{-- Form Pengaturan --}}
    <div>
        <div class="card mb-24">
            <div class="card-header">
                <h3 style="margin:0;font-size:16px;">⚙️ Konfigurasi API Gateway</h3>
            </div>
                <div style="background:rgba(37,211,102,0.08);border:1px solid rgba(37,211,102,0.25);border-radius:10px;padding:14px;margin-bottom:20px;">
                    <div style="font-weight:600;font-size:13px;color:#25d366;margin-bottom:8px;">⚡ Preset Cepat:</div>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                        <button type="button" class="btn btn-sm" style="background:#25d366;color:#000;font-weight:600;" onclick="applyPreset('openwa_mitrapedia')">
                            🌐 OpenWA (wa.mitrapedia.my.id)
                        </button>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="applyPreset('fonnte')">
                            💬 Fonnte (api.fonnte.com)
                        </button>
                    </div>
                </div>

                <form action="{{ route('whatsapp.settings.update') }}" method="POST">
                    @csrf

                    <div class="form-group">
                        <label class="form-label">Tipe Gateway WhatsApp</label>
                        <select name="gateway_type" id="gateway_type" class="form-control" onchange="toggleGatewayFields()">
                            <option value="auto" {{ ($settings['gateway_type'] ?? 'auto') === 'auto' ? 'selected' : '' }}>🔄 Otomatis (Auto-Detect)</option>
                            <option value="openwa" {{ ($settings['gateway_type'] ?? '') === 'openwa' ? 'selected' : '' }}>🟢 OpenWA Gateway (REST API / Dashboard)</option>
                            <option value="fonnte" {{ ($settings['gateway_type'] ?? '') === 'fonnte' ? 'selected' : '' }}>🔵 Fonnte / Standard Gateway</option>
                        </select>
                        <small style="color:var(--text-muted);font-size:12px;margin-top:4px;display:block;">
                            Pilih <strong>OpenWA Gateway</strong> jika menggunakan <code>wa.mitrapedia.my.id</code>.
                        </small>
                    </div>

                    <div class="form-group" id="session-group">
                        <label class="form-label">Nama Session OpenWA</label>
                        <input type="text" name="session" id="session_name" class="form-control"
                            value="{{ old('session', $settings['session'] ?? 'default') }}"
                            placeholder="default">
                        <small style="color:var(--text-muted);font-size:12px;margin-top:4px;display:block;">
                            Nama session yang terhubung/di-scan QR di OpenWA Anda (contoh: <code>default</code>).
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">URL Endpoint API <span style="color:#ef4444;">*</span></label>
                        <input type="url" name="api_url" id="api_url" class="form-control @error('api_url') is-invalid @enderror"
                            value="{{ old('api_url', $settings['api_url']) }}"
                            placeholder="https://wa.mitrapedia.my.id/api/sessions/default/messages/send-text" required>
                        <small style="color:var(--text-muted);font-size:12px;margin-top:4px;display:block;" id="url-help">
                            Untuk OpenWA: <code>https://wa.mitrapedia.my.id/api/sessions/default/messages/send-text</code> (atau cukup domain <code>https://wa.mitrapedia.my.id</code>)
                        </small>
                        @error('api_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">API Key / Token</label>
                        <input type="password" name="api_key" class="form-control" autocomplete="new-password"
                            placeholder="{{ $settings['api_key_set'] ? '••••••••••••••• (sudah dikonfigurasi)' : 'Masukkan API key OpenWA...' }}">
                        <small style="color:var(--text-muted);font-size:12px;margin-top:4px;display:block;">
                            Status: 
                            @if($settings['api_key_set'])
                                <span style="color:#25d366;">✓ Sudah dikonfigurasi</span>
                            @else
                                <span style="color:#ef4444;">✗ Belum dikonfigurasi</span>
                            @endif
                            &mdash; Diperoleh dari dashboard OpenWA menu <strong>API Keys</strong>.
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Webhook Secret (Opsional)</label>
                        <input type="text" name="webhook_secret" class="form-control"
                            value="{{ old('webhook_secret', $settings['webhook_secret']) }}"
                            placeholder="my-secret-key-123">
                        <small style="color:var(--text-muted);font-size:12px;margin-top:4px;display:block;">
                            Token rahasia untuk memverifikasi webhook yang masuk. Kosongkan jika tidak memakai secret.
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nomor WhatsApp Admin</label>
                        <input type="text" name="admin_phone" class="form-control"
                            value="{{ old('admin_phone', $settings['admin_phone']) }}"
                            placeholder="Contoh: 081234567890 atau 6281234567890">
                        <small style="color:var(--text-muted);font-size:12px;margin-top:4px;display:block;">
                            Nomor untuk menerima notifikasi pesan/voucher (bisa diawali 08... atau 62...).
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nama Brand Hotspot</label>
                        <input type="text" name="brand_name" class="form-control"
                            value="{{ old('brand_name', $settings['brand_name'] ?? '') }}"
                            placeholder="Contoh: MANTIK Hotspot, KafeNet WiFi">
                        <small style="color:var(--text-muted);font-size:12px;margin-top:4px;display:block;">
                            Nama usaha / brand hotspot yang akan ditampilkan pada header dan footer pesan voucher WhatsApp & Telegram.
                        </small>
                    </div>

                    <div style="border-top:1px solid var(--border-color);margin:24px 0 20px 0;padding-top:18px;">
                        <h4 style="margin:0 0 12px 0;font-size:14px;color:#25d366;display:flex;align-items:center;gap:8px;">
                            🎉 Mode Event & Voucher Gratis (Free Voucher)
                        </h4>
                        <div class="form-group">
                            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-weight:600;">
                                <input type="checkbox" name="event_mode" value="1" {{ !empty($settings['event_mode']) ? 'checked' : '' }}>
                                Aktifkan Mode Event Voucher Gratis
                            </label>
                            <small style="color:var(--text-muted);font-size:12px;margin-top:4px;display:block;">
                                Saat diaktifkan, semua orang (pengunjung / non-pelanggan) bisa request voucher gratis via WhatsApp. Sistem otomatis membatasi <strong>maksimal 1 voucher gratis per nomor WA dalam 1 hari</strong>.
                            </small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Pilihan Paket Voucher Event Gratis</label>
                            <select name="event_package_id" class="form-control">
                                <option value="">-- Otomatis (Paket FREE / Gratis) --</option>
                                @foreach($availablePackages as $pkg)
                                    <option value="{{ $pkg->id }}" {{ ($settings['event_package_id'] ?? '') == $pkg->id ? 'selected' : '' }}>
                                        {{ $pkg->name }} ({{ strtoupper($pkg->slug) }}) - Rp {{ number_format($pkg->price, 0, ',', '.') }}
                                    </option>
                                @endforeach
                            </select>
                            <small style="color:var(--text-muted);font-size:12px;margin-top:4px;display:block;">
                                Paket yang akan dibagikan saat pengunjung request dengan format: <code>VOUCHER#NAMA_SSID#HOTSPOT-1HARI</code> atau <code>VOUCHER#NAMA_SSID#FREE</code>.
                            </small>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%;">
                        💾 Simpan Pengaturan
                    </button>
                </form>
            </div>
        </div>

        {{-- Test Kirim --}}
        <div class="card">
            <div class="card-header">
                <h3 style="margin:0;font-size:16px;">🧪 Test Koneksi Gateway</h3>
            </div>
            <div style="padding:24px;">
                <form action="{{ route('whatsapp.test-send') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Nomor Tujuan Test</label>
                        <input type="text" name="phone" class="form-control" placeholder="Contoh: 081234567890 atau 6281234567890" required>
                        <small style="color:var(--text-muted);font-size:12px;margin-top:4px;display:block;">
                            Bisa diawali 08... atau 62...
                        </small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Pesan (opsional)</label>
                        <textarea name="message" class="form-control" rows="3" placeholder="Pesan default akan digunakan jika kosong..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-secondary" style="width:100%;">
                        📤 Kirim Pesan Test
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Info & Status Stok --}}
    <div>
        {{-- Webhook URL --}}
        <div class="card mb-24">
            <div class="card-header">
                <h3 style="margin:0;font-size:16px;">🔗 Webhook URL</h3>
            </div>
            <div style="padding:24px;">
                <p style="font-size:13px;color:var(--text-muted);margin-bottom:16px;">
                    Masukkan URL berikut ke konfigurasi <strong>Webhook / Callback</strong> di dashboard WhatsApp Gateway Anda. Sistem akan menerima pesan yang dikirim pengunjung ke nomor gateway Anda.
                </p>

                <div style="margin-bottom:12px;">
                    <label style="font-size:12px;color:var(--text-muted);display:block;margin-bottom:6px;">Webhook URL (POST/GET)</label>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <code id="webhook-url" style="flex:1;background:rgba(0,0,0,0.3);padding:10px 14px;border-radius:8px;font-size:13px;color:#00d4ff;border:1px solid rgba(0,212,255,0.2);word-break:break-all;">{{ url('/api/whatsapp/webhook') }}</code>
                        <button onclick="copyWebhookUrl()" class="btn btn-sm btn-secondary" title="Salin URL">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                        </button>
                    </div>
                </div>

                <div style="background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.2);border-radius:8px;padding:14px;margin-bottom:16px;">
                    <div style="font-size:13px;font-weight:600;color:#f59e0b;margin-bottom:8px;">📋 Format Pesan Pengunjung</div>
                    <div style="font-family:monospace;font-size:14px;color:var(--text-primary);margin-bottom:8px;">
                        <code>VOUCHER#KODE_PAKET#NAMA_ANDA</code>
                    </div>
                    <div style="font-size:12px;color:var(--text-muted);">Contoh: <code>VOUCHER#1JAM#Budi Santoso</code></div>
                    <hr style="border-color:rgba(255,255,255,0.05);margin:10px 0;">
                    <div style="font-size:12px;color:var(--text-muted);">
                        Ketik <code>INFO</code> atau <code>PAKET</code> untuk melihat daftar paket yang tersedia.
                    </div>
                </div>

                {{-- Panduan Khusus OpenWA --}}
                <div style="background:rgba(0,212,255,0.05);border:1px solid rgba(0,212,255,0.2);border-radius:8px;padding:14px;">
                    <div style="font-size:13px;font-weight:600;color:#00d4ff;margin-bottom:8px;">📘 Panduan OpenWA (wa.mitrapedia.my.id):</div>
                    <ol style="font-size:12px;color:var(--text-secondary);padding-left:18px;margin:0;display:flex;flex-direction:column;gap:6px;">
                        <li>Buka dashboard <strong><a href="https://wa.mitrapedia.my.id" target="_blank" style="color:#00d4ff;">wa.mitrapedia.my.id</a></strong>.</li>
                        <li>Buka menu <strong>Sessions</strong>, pastikan session Anda aktif (Scan QR). Ingat nama session tersebut (default: <code>default</code>).</li>
                        <li>Buka menu <strong>API Keys</strong>, buat/salin API Key lalu tempel di form sebelah kiri.</li>
                        <li>Buka menu <strong>Webhooks</strong> di dashboard OpenWA, tambahkan Webhook baru mengarah ke URL Webhook di atas dengan event <code>message</code>.</li>
                        <li>Klik <strong>Simpan Pengaturan</strong> lalu lakukan <strong>Test Koneksi</strong>.</li>
                    </ol>
                </div>
            </div>
        </div>

        {{-- Stok Voucher --}}
        <div class="card mb-24">
            <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
                <h3 style="margin:0;font-size:16px;">📦 Status Stok Voucher</h3>
                <a href="{{ route('hotspot.vouchers.generate') }}" class="btn btn-sm btn-primary">+ Generate Voucher</a>
            </div>
            <div style="padding:20px;">
                @if($stockSummary->isEmpty())
                    <p style="color:var(--text-muted);text-align:center;padding:24px 0;">Belum ada voucher yang dibuat.</p>
                @else
                    @foreach($stockSummary as $stock)
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--border-color);">
                        <div>
                            <div style="font-weight:600;font-size:14px;">{{ $stock->package ? $stock->package->name : 'Tanpa Paket' }}</div>
                            <div style="font-size:12px;color:var(--text-muted);">Total: {{ $stock->total }} voucher</div>
                        </div>
                        <div style="text-align:right;">
                            @php $available = $stock->available ?? 0; @endphp
                            <div style="font-size:20px;font-weight:700;color:{{ $available > 5 ? '#25d366' : ($available > 0 ? '#f59e0b' : '#ef4444') }}">
                                {{ $available }}
                            </div>
                            <div style="font-size:11px;color:var(--text-muted);">tersedia</div>
                        </div>
                    </div>
                    @endforeach
                @endif
            </div>
        </div>

        {{-- Alur Sistem --}}
        <div class="card">
            <div class="card-header">
                <h3 style="margin:0;font-size:16px;">🔄 Alur Sistem</h3>
            </div>
            <div style="padding:20px;">
                <div style="display:flex;flex-direction:column;gap:0;">
                    @php
                    $steps = [
                        ['icon' => '📱', 'color' => '#25d366', 'title' => 'Pengunjung Kirim Pesan', 'desc' => 'Pengunjung mengirim: VOUCHER#1JAM#Budi ke nomor WA gateway'],
                        ['icon' => '🔗', 'color' => '#00d4ff', 'title' => 'Gateway Terima & Forward', 'desc' => 'Gateway mengirim data pesan ke Webhook URL sistem ini'],
                        ['icon' => '⚙️', 'color' => '#8b5cf6', 'title' => 'Sistem Proses Request', 'desc' => 'Validasi format, cek paket, ambil voucher dari stok'],
                        ['icon' => '🎟️', 'color' => '#f59e0b', 'title' => 'Kirim Voucher ke Pengunjung', 'desc' => 'Sistem membalas otomatis dengan kode voucher dan info masa berlaku'],
                        ['icon' => '🌐', 'color' => '#10b981', 'title' => 'Login Hotspot', 'desc' => 'Pengunjung memasukkan kode voucher di halaman login hotspot'],
                    ];
                    @endphp
                    @foreach($steps as $i => $step)
                    <div style="display:flex;gap:16px;padding:12px 0;{{ $loop->last ? '' : 'border-bottom:1px solid var(--border-color);' }}">
                        <div style="flex-shrink:0;display:flex;flex-direction:column;align-items:center;">
                            <div style="width:36px;height:36px;border-radius:50%;background:{{ $step['color'] }}22;border:2px solid {{ $step['color'] }}44;display:flex;align-items:center;justify-content:center;font-size:16px;">{{ $step['icon'] }}</div>
                            @if(!$loop->last)
                            <div style="width:2px;height:20px;background:var(--border-color);margin-top:4px;"></div>
                            @endif
                        </div>
                        <div style="padding-top:6px;">
                            <div style="font-weight:600;font-size:13px;color:{{ $step['color'] }};">{{ $step['title'] }}</div>
                            <div style="font-size:12px;color:var(--text-muted);margin-top:2px;">{{ $step['desc'] }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function copyWebhookUrl() {
    const url = document.getElementById('webhook-url').textContent;
    navigator.clipboard.writeText(url).then(() => {
        alert('URL Webhook berhasil disalin!');
    });
}

function applyPreset(type) {
    const sessionVal = document.getElementById('session_name').value || 'default';
    if (type === 'openwa_mitrapedia') {
        document.getElementById('gateway_type').value = 'openwa';
        document.getElementById('api_url').value = 'https://wa.mitrapedia.my.id/api/sessions/' + sessionVal + '/messages/send-text';
        document.getElementById('session_name').value = sessionVal;
        toggleGatewayFields();
    } else if (type === 'fonnte') {
        document.getElementById('gateway_type').value = 'fonnte';
        document.getElementById('api_url').value = 'https://api.fonnte.com/send';
        toggleGatewayFields();
    }
}

function toggleGatewayFields() {
    const type = document.getElementById('gateway_type').value;
    const sessionGroup = document.getElementById('session-group');
    if (type === 'fonnte') {
        sessionGroup.style.display = 'none';
    } else {
        sessionGroup.style.display = 'block';
    }
}

document.addEventListener('DOMContentLoaded', toggleGatewayFields);
</script>
@endpush
