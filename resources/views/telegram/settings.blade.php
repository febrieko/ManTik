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
        <p>Konfigurasi koneksi webhook untuk mengaktifkan bot Telegram Anda</p>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success mb-24">✅ {{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger mb-24">❌ {{ session('error') }}</div>
@endif

<div class="card mb-24">
    <div class="card-header">
        <h3 style="margin:0;font-size:16px;">Konfigurasi Webhook</h3>
    </div>
    <div style="padding:24px;">
        <div style="margin-bottom:16px;">
            <label class="form-label">Telegram Bot Token (dari .env)</label>
            <input type="text" class="form-control" value="{{ $botToken ? '•••••••••••••••••••••••••••••••••••••••••••••' : '' }}" disabled placeholder="Token belum diset di .env">
            @if(!$botToken)
                <small style="color:#ef4444;margin-top:4px;display:block;">Token belum ditemukan. Silakan tambahkan TELEGRAM_BOT_TOKEN di file .env Anda.</small>
            @endif
        </div>
        
        <div style="margin-bottom:16px;">
            <label class="form-label">Webhook URL (Target)</label>
            <div style="display:flex;gap:8px;align-items:center;">
                <code style="flex:1;background:rgba(0,0,0,0.3);padding:10px 14px;border-radius:8px;font-size:13px;color:#00d4ff;border:1px solid rgba(0,212,255,0.2);">{{ url('/api/telegram/webhook') }}</code>
            </div>
        </div>

        <form action="{{ route('telegram.set-webhook') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-primary" {{ !$botToken ? 'disabled' : '' }}>
                🚀 Set Webhook Sekarang
            </button>
            <small style="color:var(--text-muted);display:block;margin-top:10px;">Klik tombol ini agar Telegram mengetahui kemana harus mengirimkan pesan yang masuk ke bot Anda. Ini wajib dilakukan sekali.</small>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 style="margin:0;font-size:16px;">Langkah Pembuatan Bot</h3>
    </div>
    <div style="padding:24px;">
        <ol style="margin:0;padding-left:20px;color:var(--text-primary);line-height:1.6;">
            <li>Buka aplikasi Telegram dan cari <strong>@BotFather</strong>.</li>
            <li>Kirim pesan <code>/newbot</code> dan ikuti instruksi (masukkan nama bot dan username).</li>
            <li>Setelah selesai, BotFather akan memberikan sebuah <strong>HTTP API Token</strong>.</li>
            <li>Buka file <code>.env</code> di server Anda, tambahkan baris ini: <br><code>TELEGRAM_BOT_TOKEN=token_anda_disini</code></li>
            <li>Kembali ke halaman ini dan klik tombol <strong>Set Webhook Sekarang</strong>.</li>
            <li>Coba kirim <code>/start</code> ke bot Anda di Telegram!</li>
        </ol>
    </div>
</div>
@endsection
