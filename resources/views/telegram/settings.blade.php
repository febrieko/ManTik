@extends('layouts.app')

@section('title', 'Pengaturan Telegram Bot')
@section('page-title', 'Pengaturan Telegram Bot')

@section('content')
<div class="page-header">
    <div class="page-header-title">
        <h2 style="display:flex;align-items:center;gap:10px;">
            <span style="display:inline-flex;width:36px;height:36px;background:linear-gradient(135deg,#0088cc,#00b0f0);border-radius:10px;align-items:center;justify-content:center;">
                <svg viewBox="0 0 24 24" fill="white" width="20" height="20">
                    <path d="M20.665 3.717l-17.73 6.837c-1.21.486-1.203 1.161-.222 1.462l4.552 1.42 10.532-6.645c.498-.303.953-.14.579.192l-8.533 7.701h-.002l.002.001-.314 4.692c.46 0 .663-.211.921-.46l2.211-2.15 4.599 3.397c.848.467 1.457.227 1.668-.785l3.019-14.228c.309-1.239-.473-1.8-1.282-1.434z"/>
                </svg>
            </span>
            Pengaturan Telegram Bot
        </h2>
        <p>Konfigurasi kredensial bot Telegram, koneksi webhook, dan notifikasi gateway</p>
    </div>
    <div style="display:flex;gap:10px;">
        <a href="{{ route('telegram.users') }}" class="btn btn-secondary">
            👥 Pengguna Bot
        </a>
        <a href="{{ route('telegram.requests') }}" class="btn btn-secondary">
            📋 Riwayat Request
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success mb-24">✅ {{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger mb-24">❌ {{ session('error') }}</div>
@endif

<div style="display:grid;grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));gap:24px;align-items:start;">

    {{-- Kolom Kiri: Form Input Bot Token & Pengaturan --}}
    <div>
        {{-- Card: Kredensial Bot --}}
        <div class="card mb-24">
            <div class="card-header">
                <h3 style="margin:0;font-size:16px;">🤖 Kredensial Bot Telegram</h3>
            </div>
            <div style="padding:24px;">
                <form action="{{ route('telegram.settings.update') }}" method="POST">
                    @csrf

                    <div class="form-group mb-16">
                        <label class="form-label" for="bot_token">
                            Telegram Bot Token <span style="color:#ef4444;">*</span>
                        </label>
                        <div style="position:relative;">
                            <input type="password" id="bot_token" name="bot_token" 
                                class="form-control @error('bot_token') is-invalid @enderror" 
                                value="{{ old('bot_token', $botToken) }}" 
                                placeholder="Contoh: 123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ"
                                style="padding-right:45px;"
                                autocomplete="new-password">
                            <button type="button" onclick="togglePasswordVisibility('bot_token', this)" 
                                style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);padding:4px;"
                                title="Tampilkan/Sembunyikan Token">
                                👁️
                            </button>
                        </div>
                        <small style="color:var(--text-muted);font-size:12px;margin-top:4px;display:block;">
                            Status: 
                            @if($botToken)
                                <span style="color:#10b981;font-weight:600;">✓ Token Tersimpan</span>
                            @else
                                <span style="color:#ef4444;font-weight:600;">✗ Belum Ada Token</span>
                            @endif
                            &mdash; Diperoleh dari <strong>@BotFather</strong> saat membuat bot Telegram.
                        </small>
                        @error('bot_token') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-group mb-20">
                        <label class="form-label" for="telegram_admin_chat_id">
                            Telegram Admin Chat ID (Penerima Notifikasi)
                        </label>
                        <input type="text" id="telegram_admin_chat_id" name="telegram_admin_chat_id" 
                            class="form-control @error('telegram_admin_chat_id') is-invalid @enderror" 
                            value="{{ old('telegram_admin_chat_id', $adminChatId) }}" 
                            placeholder="Contoh: 123456789 atau -1001234567890 (jika grup)">
                        <small style="color:var(--text-muted);font-size:12px;margin-top:4px;display:block;">
                            ID Telegram Anda atau Group ID tempat bot mengirim notifikasi router down atau voucher. Cari tahu ID Anda di Telegram lewat bot <code>@userinfobot</code>.
                        </small>
                        @error('telegram_admin_chat_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%;">
                        💾 Simpan Pengaturan Bot
                    </button>
                </form>
            </div>
        </div>

        {{-- Card: Webhook --}}
        <div class="card mb-24">
            <div class="card-header">
                <h3 style="margin:0;font-size:16px;">⚡ Webhook Bot</h3>
            </div>
            <div style="padding:24px;">
                <div style="margin-bottom:16px;">
                    <label class="form-label">Webhook URL (Target)</label>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <code id="webhook_url" style="flex:1;background:rgba(0,0,0,0.3);padding:10px 14px;border-radius:8px;font-size:13px;color:#00d4ff;border:1px solid rgba(0,212,255,0.2);word-break:break-all;">{{ url('/api/telegram/webhook') }}</code>
                    </div>
                    <small style="color:var(--text-muted);font-size:12px;margin-top:6px;display:block;">
                        Pastikan domain Anda dapat diakses publik dengan HTTPS (misal menggunakan Cloudflare Tunnel atau Ngrok jika di localhost).
                    </small>
                </div>

                @if(isset($webhookInfo['ok']) && $webhookInfo['ok'])
                <div style="background:rgba(255,255,255,0.03);border:1px solid var(--border-color, #333);border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:13px;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                        <span style="color:var(--text-muted);">Status Webhook di Telegram:</span>
                        <strong style="color:{{ !empty($webhookInfo['result']['url']) ? '#10b981' : '#f59e0b' }};">
                            {{ !empty($webhookInfo['result']['url']) ? 'Terdaftar' : 'Belum Terdaftar' }}
                        </strong>
                    </div>
                    @if(!empty($webhookInfo['result']['url']))
                    <div style="color:var(--text-muted);font-size:12px;margin-bottom:4px;word-break:break-all;">
                        URL saat ini: <code>{{ $webhookInfo['result']['url'] }}</code>
                    </div>
                    @endif
                    @if(isset($webhookInfo['result']['pending_update_count']))
                    <div style="color:var(--text-muted);font-size:12px;">
                        Pending Updates: <strong>{{ $webhookInfo['result']['pending_update_count'] }}</strong>
                    </div>
                    @endif
                    @if(!empty($webhookInfo['result']['last_error_message']))
                    <div style="color:#ef4444;font-size:12px;margin-top:6px;">
                        ⚠️ Error Terakhir: {{ $webhookInfo['result']['last_error_message'] }}
                    </div>
                    @endif
                </div>
                @endif

                <form action="{{ route('telegram.set-webhook') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary" {{ !$botToken ? 'disabled' : '' }} style="width:100%;">
                        🚀 Set / Daftarkan Webhook Sekarang
                    </button>
                    <small style="color:var(--text-muted);display:block;margin-top:8px;text-align:center;">
                        Klik tombol ini setiap kali mengubah domain / URL server Anda.
                    </small>
                </form>
            </div>
        </div>
    </div>

    {{-- Kolom Kanan: Status Bot, Test Pesan & Panduan --}}
    <div>
        {{-- Card: Status Koneksi Bot --}}
        <div class="card mb-24">
            <div class="card-header">
                <h3 style="margin:0;font-size:16px;">📡 Status Koneksi Bot</h3>
            </div>
            <div style="padding:24px;">
                @if(isset($botInfo['ok']) && $botInfo['ok'])
                    <div style="display:flex;align-items:center;gap:16px;padding:16px;background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.3);border-radius:10px;margin-bottom:16px;">
                        <span style="display:inline-flex;width:48px;height:48px;background:#10b981;border-radius:50%;align-items:center;justify-content:center;color:white;font-size:24px;">
                            ✓
                        </span>
                        <div>
                            <div style="font-weight:700;color:#10b981;font-size:16px;margin-bottom:2px;">Bot Terhubung & Aktif</div>
                            <div style="font-size:14px;color:var(--text-primary);">
                                <strong>{{ $botInfo['result']['first_name'] ?? 'Bot' }}</strong> 
                                @if(!empty($botInfo['result']['username']))
                                    (<a href="https://t.me/{{ $botInfo['result']['username'] }}" target="_blank" style="color:#0088cc;text-decoration:none;">@{{ $botInfo['result']['username'] }}</a>)
                                @endif
                            </div>
                            <div style="font-size:12px;color:var(--text-muted);margin-top:2px;">
                                Bot ID: <code>{{ $botInfo['result']['id'] ?? '-' }}</code>
                            </div>
                        </div>
                    </div>
                    @if(!empty($botInfo['result']['username']))
                    <a href="https://t.me/{{ $botInfo['result']['username'] }}" target="_blank" class="btn btn-secondary" style="width:100%;text-align:center;display:block;">
                        💬 Buka Bot di Telegram (@{{ $botInfo['result']['username'] }})
                    </a>
                    @endif
                @elseif($botToken)
                    <div style="padding:16px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:10px;">
                        <div style="font-weight:700;color:#ef4444;font-size:15px;margin-bottom:4px;">❌ Gagal Terkoneksi ke Bot</div>
                        <p style="font-size:13px;color:var(--text-primary);margin:0;">
                            {{ $botInfo['description'] ?? 'Token bot tidak valid atau koneksi internet gagal menghubungi server Telegram.' }}
                        </p>
                    </div>
                @else
                    <div style="padding:16px;background:rgba(255,255,255,0.05);border:1px dashed var(--border-color, #444);border-radius:10px;text-align:center;color:var(--text-muted);">
                        <p style="margin:0;font-size:14px;">Masukkan Bot Token di sebelah kiri untuk menghubungkan bot Anda.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Card: Test Kirim Pesan --}}
        <div class="card mb-24">
            <div class="card-header">
                <h3 style="margin:0;font-size:16px;">🧪 Tes Kirim Pesan Bot</h3>
            </div>
            <div style="padding:24px;">
                <form action="{{ route('telegram.test-send') }}" method="POST">
                    @csrf
                    <div class="form-group mb-16">
                        <label class="form-label" for="test_chat_id">Target Chat ID <span style="color:#ef4444;">*</span></label>
                        <input type="text" id="test_chat_id" name="chat_id" class="form-control" 
                            value="{{ old('chat_id', $adminChatId) }}" 
                            placeholder="Contoh: 123456789" required>
                        <small style="color:var(--text-muted);font-size:12px;margin-top:4px;display:block;">
                            Pastikan Anda sudah menekan tombol <strong>Start</strong> di bot tersebut sebelum mengirim pesan tes.
                        </small>
                    </div>
                    <div class="form-group mb-16">
                        <label class="form-label" for="test_message">Pesan Tes</label>
                        <textarea id="test_message" name="message" class="form-control" rows="2" 
                            placeholder="Tulis pesan tes...">🔔 Tes koneksi bot Telegram MANTIK berhasil!</textarea>
                    </div>
                    <button type="submit" class="btn btn-secondary" style="width:100%;" {{ !$botToken ? 'disabled' : '' }}>
                        ✉️ Kirim Pesan Tes Sekarang
                    </button>
                </form>
            </div>
        </div>

        {{-- Card: Panduan --}}
        <div class="card">
            <div class="card-header">
                <h3 style="margin:0;font-size:16px;">📖 Cara Membuat Bot Telegram</h3>
            </div>
            <div style="padding:24px;">
                <ol style="margin:0;padding-left:20px;color:var(--text-primary);line-height:1.7;font-size:13px;">
                    <li>Buka aplikasi Telegram dan cari <strong>@BotFather</strong> (dengan centang biru resmi).</li>
                    <li>Kirim perintah <code>/newbot</code>.</li>
                    <li>Ikuti instruksi dengan memasukkan nama tampilan bot dan username bot (harus berakhiran <code>bot</code>, misal: <code>mantik_wifi_bot</code>).</li>
                    <li>BotFather akan memberikan <strong>HTTP API Token</strong>. Salin kode token tersebut.</li>
                    <li>Tempelkan token ke kolom <strong>Telegram Bot Token</strong> di halaman ini, lalu klik <strong>Simpan Pengaturan Bot</strong>.</li>
                    <li>Klik tombol <strong>Set / Daftarkan Webhook Sekarang</strong>.</li>
                    <li>Cari bot Anda di Telegram dan ketik <code>/start</code> untuk mulai menggunakan!</li>
                </ol>
            </div>
        </div>
    </div>

</div>

<script>
function togglePasswordVisibility(id, btn) {
    const input = document.getElementById(id);
    if (input.type === 'password') {
        input.type = 'text';
        btn.textContent = '🔒';
    } else {
        input.type = 'password';
        btn.textContent = '👁️';
    }
}
</script>
@endsection
