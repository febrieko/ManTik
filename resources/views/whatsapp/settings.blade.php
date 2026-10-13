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

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start;">

    {{-- Form Pengaturan --}}
    <div>
        <div class="card mb-24">
            <div class="card-header">
                <h3 style="margin:0;font-size:16px;">⚙️ Konfigurasi API Gateway</h3>
            </div>
            <div style="padding:24px;">
                <form action="{{ route('whatsapp.settings.update') }}" method="POST">
                    @csrf

                    <div class="form-group">
                        <label class="form-label">URL Endpoint API <span style="color:#ef4444;">*</span></label>
                        <input type="url" name="api_url" class="form-control @error('api_url') is-invalid @enderror"
                            value="{{ old('api_url', $settings['api_url']) }}"
                            placeholder="https://api.yourgatewayprovider.com/send" required>
                        <small style="color:var(--text-muted);font-size:12px;margin-top:4px;display:block;">
                            URL endpoint untuk mengirim pesan. Contoh: <code>https://api.fonnte.com/send</code>
                        </small>
                        @error('api_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">API Key / Token</label>
                        <input type="password" name="api_key" class="form-control" autocomplete="new-password"
                            placeholder="{{ $settings['api_key_set'] ? '••••••••••••••• (sudah dikonfigurasi)' : 'Masukkan API key...' }}">
                        <small style="color:var(--text-muted);font-size:12px;margin-top:4px;display:block;">
                            Status: 
                            @if($settings['api_key_set'])
                                <span style="color:#25d366;">✓ Sudah dikonfigurasi</span>
                            @else
                                <span style="color:#ef4444;">✗ Belum dikonfigurasi</span>
                            @endif
                            &mdash; Kosongkan jika tidak ingin mengubah.
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Webhook Secret</label>
                        <input type="text" name="webhook_secret" class="form-control"
                            value="{{ old('webhook_secret', $settings['webhook_secret']) }}"
                            placeholder="my-secret-key-123">
                        <small style="color:var(--text-muted);font-size:12px;margin-top:4px;display:block;">
                            Token rahasia untuk memverifikasi bahwa webhook berasal dari gateway Anda. Kosongkan untuk menonaktifkan validasi.
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nomor WhatsApp Admin</label>
                        <input type="text" name="admin_phone" class="form-control"
                            value="{{ old('admin_phone', $settings['admin_phone']) }}"
                            placeholder="6281234567890">
                        <small style="color:var(--text-muted);font-size:12px;margin-top:4px;display:block;">
                            Nomor yang akan menerima notifikasi setiap ada voucher yang diminta. Format: 628xxxxxxxxx. Kosongkan untuk menonaktifkan.
                        </small>
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
                        <input type="text" name="phone" class="form-control" placeholder="6281234567890" required>
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

                <div style="background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.2);border-radius:8px;padding:14px;">
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
</script>
@endpush
