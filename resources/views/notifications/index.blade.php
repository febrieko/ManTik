@extends('layouts.app')

@section('title', 'Pusat Notifikasi')
@section('page-title', 'Pusat Notifikasi')

@section('content')
<div class="page-header">
    <div class="page-header-title">
        <h2>Pusat Notifikasi & Integrasi Gateway</h2>
        <p>Kelola notifikasi otomatis router online/offline, voucher kadaluarsa, dan saldo pelanggan via WhatsApp & Telegram</p>
    </div>
    <div class="d-flex gap-8 align-center">
        <form action="{{ route('notifications.scan-customers') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-outline" style="color:#00d4ff;border-color:rgba(0,212,255,0.4);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;margin-right:6px;">
                    <path d="M23 4v6h-6M1 20v-6h6"/>
                    <path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/>
                </svg>
                Pindai Voucher & Saldo Sekarang
            </button>
        </form>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success mb-24" style="background:rgba(0,230,118,0.1);border:1px solid rgba(0,230,118,0.3);color:#00e676;padding:12px 16px;border-radius:10px;display:flex;align-items:center;gap:10px;">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px;"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    <span>{{ session('success') }}</span>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger mb-24" style="background:rgba(255,68,68,0.1);border:1px solid rgba(255,68,68,0.3);color:#ff4444;padding:12px 16px;border-radius:10px;display:flex;align-items:center;gap:10px;">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <span>{{ session('error') }}</span>
</div>
@endif

{{-- Summary Stats Row --}}
<div class="grid grid-4 mb-24">
    <div class="stat-card cyan">
        <div class="stat-icon cyan">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0"/>
            </svg>
        </div>
        <div class="stat-value">{{ $stats['total'] }}</div>
        <div class="stat-label">Total Pesan Tercatat</div>
    </div>

    <div class="stat-card green">
        <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
        </div>
        <div class="stat-value" style="color:#00e676;">{{ $stats['sent'] }}</div>
        <div class="stat-label">Berhasil Terkirim</div>
    </div>

    <div class="stat-card red">
        <div class="stat-icon red">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
            </svg>
        </div>
        <div class="stat-value" style="color:#ff4444;">{{ $stats['failed'] }}</div>
        <div class="stat-label">Gagal Terkirim</div>
    </div>

    <div class="stat-card blue">
        <div class="stat-icon blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
            </svg>
        </div>
        <div class="stat-value">{{ $stats['today_count'] }}</div>
        <div class="stat-label">Notifikasi Hari Ini</div>
    </div>
</div>

{{-- Navigation Tabs --}}
@php
    $activeTab = request('tab', 'logs');
@endphp
<div class="tabs-nav mb-24 d-flex gap-8" style="border-bottom:1px solid var(--border);padding-bottom:10px;">
    <button type="button" class="tab-btn {{ $activeTab === 'logs' ? 'active' : '' }}" onclick="switchTab('logs')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;margin-right:6px;"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        Riwayat Log Notifikasi
    </button>
    <button type="button" class="tab-btn {{ $activeTab === 'settings' ? 'active' : '' }}" onclick="switchTab('settings')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;margin-right:6px;"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
        Pengaturan Channel & Pemicu
    </button>
    <button type="button" class="tab-btn {{ $activeTab === 'test' ? 'active' : '' }}" onclick="switchTab('test')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;margin-right:6px;"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
        Uji Coba Kirim (Test)
    </button>
</div>

{{-- TAB 1: RIWAYAT LOG --}}
<div id="tab-content-logs" class="tab-pane {{ $activeTab === 'logs' ? 'd-block' : 'd-none' }}">
    <div class="card">
        <div class="card-header d-flex justify-between align-center">
            <div>
                <h3 style="font-size:16px;font-weight:600;color:var(--text-primary);">Daftar Log Pengiriman Notifikasi</h3>
                <p style="font-size:12px;color:var(--text-secondary);margin-top:2px;">Rekaman pengiriman pesan WhatsApp dan Telegram yang dipicu oleh sistem</p>
            </div>
        </div>

        {{-- Filters --}}
        <div style="padding:16px 20px;background:rgba(255,255,255,0.02);border-bottom:1px solid var(--border);">
            <form action="{{ route('notifications.index') }}" method="GET" class="d-flex gap-12 align-center flex-wrap">
                <input type="hidden" name="tab" value="logs">
                <div style="min-width:170px;">
                    <select name="type" class="form-control text-sm">
                        <option value="">-- Semua Tipe --</option>
                        <option value="router_down" {{ request('type') === 'router_down' ? 'selected' : '' }}>🔴 Router Down</option>
                        <option value="router_up" {{ request('type') === 'router_up' ? 'selected' : '' }}>🟢 Router Up (Recovered)</option>
                        <option value="voucher_expiring" {{ request('type') === 'voucher_expiring' ? 'selected' : '' }}>⏳ Voucher Kadaluarsa</option>
                        <option value="balance_low" {{ request('type') === 'balance_low' ? 'selected' : '' }}>⚠️ Saldo Menipis</option>
                        <option value="custom" {{ request('type') === 'custom' ? 'selected' : '' }}>📨 Test / Manual</option>
                    </select>
                </div>

                <div style="min-width:140px;">
                    <select name="channel" class="form-control text-sm">
                        <option value="">-- Semua Channel --</option>
                        <option value="whatsapp" {{ request('channel') === 'whatsapp' ? 'selected' : '' }}>💬 WhatsApp</option>
                        <option value="telegram" {{ request('channel') === 'telegram' ? 'selected' : '' }}>✈️ Telegram</option>
                    </select>
                </div>

                <div style="min-width:130px;">
                    <select name="status" class="form-control text-sm">
                        <option value="">-- Status --</option>
                        <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>✅ Terkirim</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>❌ Gagal</option>
                    </select>
                </div>

                <div>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control text-sm" placeholder="Dari">
                </div>

                <div>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control text-sm" placeholder="Sampai">
                </div>

                <div style="flex:1;min-width:160px;">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control text-sm" placeholder="Cari penerima / subjek...">
                </div>

                <div class="d-flex gap-6">
                    <button type="submit" class="btn btn-outline-primary text-sm" style="padding:7px 14px;">Filter</button>
                    @if(request()->hasAny(['type', 'channel', 'status', 'date_from', 'date_to', 'search']))
                    <a href="{{ route('notifications.index', ['tab' => 'logs']) }}" class="btn btn-outline text-sm" style="padding:7px 14px;">Reset</a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width:160px;">Waktu</th>
                        <th style="width:140px;">Tipe</th>
                        <th style="width:110px;">Channel</th>
                        <th style="width:160px;">Penerima</th>
                        <th>Subjek & Cuplikan Pesan</th>
                        <th style="width:110px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td>
                            <div style="font-weight:500;">{{ $log->created_at->translatedFormat('d M Y') }}</div>
                            <div class="text-xs text-muted">{{ $log->created_at->format('H:i:s') }}</div>
                        </td>
                        <td>
                            @if($log->type === 'router_down')
                                <span class="badge badge-danger">🔴 Router Down</span>
                            @elseif($log->type === 'router_up')
                                <span class="badge badge-success">🟢 Router Up</span>
                            @elseif($log->type === 'voucher_expiring')
                                <span class="badge badge-warning">⏳ Expire Soon</span>
                            @elseif($log->type === 'balance_low')
                                <span class="badge" style="background:rgba(255,179,0,0.15);color:#ffb300;border:1px solid rgba(255,179,0,0.3);">⚠️ Saldo Rendah</span>
                            @else
                                <span class="badge badge-muted">{{ ucfirst($log->type) }}</span>
                            @endif
                        </td>
                        <td>
                            @if($log->channel === 'whatsapp')
                                <span class="badge" style="background:rgba(37,211,102,0.15);color:#25d366;border:1px solid rgba(37,211,102,0.3);">
                                    WhatsApp
                                </span>
                            @elseif($log->channel === 'telegram')
                                <span class="badge" style="background:rgba(0,176,255,0.15);color:#00b0ff;border:1px solid rgba(0,176,255,0.3);">
                                    Telegram
                                </span>
                            @else
                                <span class="badge badge-muted">{{ ucfirst($log->channel) }}</span>
                            @endif
                        </td>
                        <td>
                            <div style="font-weight:600;font-family:monospace;font-size:12px;">{{ $log->recipient ?: '-' }}</div>
                            @if($log->router)
                                <div class="text-xs text-muted">Router: {{ $log->router->name }}</div>
                            @elseif($log->customer)
                                <div class="text-xs text-muted">Cust: {{ $log->customer->name }}</div>
                            @endif
                        </td>
                        <td>
                            @if($log->subject)
                                <div style="font-weight:600;font-size:13px;color:var(--text-primary);margin-bottom:2px;">
                                    {{ $log->subject }}
                                </div>
                            @endif
                            <div class="text-xs text-muted" style="white-space:pre-line;max-height:48px;overflow:hidden;line-height:1.4;">
                                {{ Str::limit($log->message, 120) }}
                            </div>
                        </td>
                        <td>
                            @if($log->status === 'sent')
                                <span class="badge badge-success">Terkirim</span>
                            @elseif($log->status === 'failed')
                                <span class="badge badge-danger" title="{{ $log->error_message ?? 'Gagal terkirim' }}">Gagal</span>
                                @if($log->error_message)
                                    <div class="text-xs text-muted" style="color:#ff8080;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                        {{ $log->error_message }}
                                    </div>
                                @endif
                            @else
                                <span class="badge badge-muted">Pending</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center" style="padding:40px;color:var(--text-muted);">
                            Belum ada riwayat pengiriman notifikasi yang tercatat.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
        <div style="padding:16px 20px;border-top:1px solid var(--border);">
            {{ $logs->links() }}
        </div>
        @endif
    </div>
</div>

{{-- TAB 2: PENGATURAN --}}
<div id="tab-content-settings" class="tab-pane {{ $activeTab === 'settings' ? 'd-block' : 'd-none' }}">
    <form action="{{ route('notifications.settings.update') }}" method="POST">
        @csrf

        {{-- Gateway Status Banner --}}
        <div class="grid grid-2 mb-24" style="gap:16px;">
            <div class="card" style="padding:16px;border-left:4px solid {{ $gateways['whatsapp_configured'] ? '#25d366' : '#ffb300' }};">
                <div class="d-flex justify-between align-center">
                    <div>
                        <h4 style="font-size:14px;color:var(--text-primary);margin-bottom:2px;">Status Gateway WhatsApp</h4>
                        <p class="text-xs text-muted">Penyedia API Fonnte / Gateway kustom</p>
                    </div>
                    <span class="badge {{ $gateways['whatsapp_configured'] ? 'badge-success' : 'badge-warning' }}">
                        {{ $gateways['whatsapp_configured'] ? 'Terhubung' : 'Belum Lengkap' }}
                    </span>
                </div>
            </div>
            <div class="card" style="padding:16px;border-left:4px solid {{ $gateways['telegram_configured'] ? '#00b0ff' : '#ffb300' }};">
                <div class="d-flex justify-between align-center">
                    <div>
                        <h4 style="font-size:14px;color:var(--text-primary);margin-bottom:2px;">Status Bot Telegram</h4>
                        <p class="text-xs text-muted">Telegram Bot Token resmi</p>
                    </div>
                    <span class="badge {{ $gateways['telegram_configured'] ? 'badge-success' : 'badge-warning' }}">
                        {{ $gateways['telegram_configured'] ? 'Terhubung' : 'Token Kosong' }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Section 1: Router Status Alerts --}}
        <div class="card mb-24">
            <div class="card-header">
                <h3 style="font-size:16px;font-weight:600;color:var(--text-primary);display:flex;align-items:center;gap:8px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;color:#00d4ff;">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    1. Notifikasi Router Online & Offline (Super Admin, Admin, Teknisi)
                </h3>
                <p style="font-size:12px;color:var(--text-secondary);margin-top:2px;">
                    Sistem otomatis mengirimkan pesan saat perangkat MikroTik mati (DOWN) atau kembali menyala (RECOVERED)
                </p>
            </div>
            <div style="padding:20px;">
                <div class="form-group mb-16">
                    <label class="d-flex align-center gap-8 cursor-pointer">
                        <input type="checkbox" name="router_alert_enabled" value="1" {{ $settings['router_alert_enabled'] ? 'checked' : '' }} style="width:18px;height:18px;">
                        <strong style="color:var(--text-primary);">Aktifkan Peringatan Otomatis Router Online/Offline</strong>
                    </label>
                </div>

                <div class="form-group mb-20">
                    <label class="form-label" style="font-weight:600;">Pilihan Saluran Pengiriman (Channel):</label>
                    <div class="d-flex gap-20 flex-wrap" style="padding:10px 14px;background:rgba(255,255,255,0.02);border-radius:8px;border:1px solid var(--border);">
                        <label class="d-flex align-center gap-6 cursor-pointer">
                            <input type="radio" name="router_alert_channel" value="whatsapp" {{ $settings['router_alert_channel'] === 'whatsapp' ? 'checked' : '' }}>
                            <span>💬 Hanya WhatsApp</span>
                        </label>
                        <label class="d-flex align-center gap-6 cursor-pointer">
                            <input type="radio" name="router_alert_channel" value="telegram" {{ $settings['router_alert_channel'] === 'telegram' ? 'checked' : '' }}>
                            <span>✈️ Hanya Telegram</span>
                        </label>
                        <label class="d-flex align-center gap-6 cursor-pointer">
                            <input type="radio" name="router_alert_channel" value="both" {{ $settings['router_alert_channel'] === 'both' ? 'checked' : '' }}>
                            <span style="color:#00d4ff;font-weight:600;">✨ Keduanya Aktif (WhatsApp & Telegram)</span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-2 mb-16" style="gap:16px;">
                    <div class="form-group">
                        <label class="form-label">Nomor WhatsApp Admin Global (Opsional):</label>
                        <input type="text" name="whatsapp_admin_phone" value="{{ $settings['whatsapp_admin_phone'] }}" class="form-control" placeholder="Contoh: 081234567890 atau 6281234567890">
                        <small class="text-xs text-muted">Nomor kontak admin pusat yang menerima seluruh alert router (bisa diawali 08... atau 62...).</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Telegram Chat ID / Group ID Admin (Opsional):</label>
                        <input type="text" name="telegram_admin_chat_id" value="{{ $settings['telegram_admin_chat_id'] }}" class="form-control" placeholder="Contoh: -100123456789 atau 987654321">
                        <small class="text-xs text-muted">Bisa berupa ID user atau ID grup/channel teknisi Telegram.</small>
                    </div>
                </div>

                {{-- Info Box: Distribusi Notifikasi & Format Nomor Telepon --}}
                <div style="background:rgba(0,212,255,0.06);border:1px solid rgba(0,212,255,0.25);border-radius:8px;padding:12px 16px;font-size:12px;color:var(--text-secondary);line-height:1.6;">
                    <strong style="color:#00d4ff;display:flex;align-items:center;gap:6px;margin-bottom:6px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                        Ketentuan Distribusi Notifikasi Router & Format Nomor:
                    </strong>
                    <ul style="margin:0;padding-left:18px;">
                        <li><strong>Super Admin & Admin:</strong> Menerima notifikasi offline & online untuk <em>seluruh router</em>.</li>
                        <li><strong>Teknisi:</strong> <em>Hanya menerima notifikasi offline & online dari router yang sudah dienrol / ditugaskan</em> ke akun teknisi tersebut.</li>
                        <li><strong>Format Nomor Telepon:</strong> Penulisan nomor WhatsApp bebas diawali angka <code>0</code> (contoh: <code>08123456789</code>) maupun <code>62</code> (contoh: <code>628123456789</code>). Sistem otomatis menormalisasinya ke format standar internasional WhatsApp.</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Section 2: Customer Voucher Expiry Alerts --}}
        <div class="card mb-24">
            <div class="card-header">
                <h3 style="font-size:16px;font-weight:600;color:var(--text-primary);display:flex;align-items:center;gap:8px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;color:#ffb300;">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    2. Notifikasi Masa Aktif Voucher Pelanggan Hampir Kadaluarsa
                </h3>
                <p style="font-size:12px;color:var(--text-secondary);margin-top:2px;">
                    Mengirim pesan pengingat kepada pengunjung atau pelanggan sebelum masa aktif voucher habis
                </p>
            </div>
            <div style="padding:20px;">
                <div class="form-group mb-16">
                    <label class="d-flex align-center gap-8 cursor-pointer">
                        <input type="checkbox" name="customer_voucher_alert_enabled" value="1" {{ $settings['customer_voucher_alert_enabled'] ? 'checked' : '' }} style="width:18px;height:18px;">
                        <strong style="color:var(--text-primary);">Aktifkan Pengingat Masa Aktif Voucher Kadaluarsa</strong>
                    </label>
                </div>

                <div class="form-group mb-20">
                    <label class="form-label" style="font-weight:600;">Saluran Pengiriman ke Pelanggan:</label>
                    <div class="d-flex gap-20 flex-wrap" style="padding:10px 14px;background:rgba(255,255,255,0.02);border-radius:8px;border:1px solid var(--border);">
                        <label class="d-flex align-center gap-6 cursor-pointer">
                            <input type="radio" name="customer_voucher_alert_channel" value="whatsapp" {{ $settings['customer_voucher_alert_channel'] === 'whatsapp' ? 'checked' : '' }}>
                            <span>💬 WhatsApp</span>
                        </label>
                        <label class="d-flex align-center gap-6 cursor-pointer">
                            <input type="radio" name="customer_voucher_alert_channel" value="telegram" {{ $settings['customer_voucher_alert_channel'] === 'telegram' ? 'checked' : '' }}>
                            <span>✈️ Telegram</span>
                        </label>
                        <label class="d-flex align-center gap-6 cursor-pointer">
                            <input type="radio" name="customer_voucher_alert_channel" value="both" {{ $settings['customer_voucher_alert_channel'] === 'both' ? 'checked' : '' }}>
                            <span style="color:#00d4ff;font-weight:600;">✨ Keduanya Aktif</span>
                        </label>
                    </div>
                </div>

                <div class="form-group" style="max-width:320px;">
                    <label class="form-label">Kirim Peringatan Berapa Jam Sebelum Kadaluarsa?</label>
                    <div class="d-flex align-center gap-8">
                        <input type="number" name="customer_voucher_expiry_hours" value="{{ $settings['customer_voucher_expiry_hours'] }}" min="1" max="72" class="form-control" style="width:100px;">
                        <span class="text-muted">Jam sebelum expired</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 3: Customer Balance Low Alerts --}}
        <div class="card mb-24">
            <div class="card-header">
                <h3 style="font-size:16px;font-weight:600;color:var(--text-primary);display:flex;align-items:center;gap:8px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;color:#00e676;">
                        <rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/>
                    </svg>
                    3. Notifikasi Saldo Pelanggan Hampir Habis
                </h3>
                <p style="font-size:12px;color:var(--text-secondary);margin-top:2px;">
                    Mengirim peringatan kepada pelanggan atau pengguna bot jika sisa saldo deposit menipis
                </p>
            </div>
            <div style="padding:20px;">
                <div class="form-group mb-16">
                    <label class="d-flex align-center gap-8 cursor-pointer">
                        <input type="checkbox" name="customer_balance_alert_enabled" value="1" {{ $settings['customer_balance_alert_enabled'] ? 'checked' : '' }} style="width:18px;height:18px;">
                        <strong style="color:var(--text-primary);">Aktifkan Peringatan Saldo Pelanggan Menipis</strong>
                    </label>
                </div>

                <div class="form-group mb-20">
                    <label class="form-label" style="font-weight:600;">Saluran Pengiriman ke Pelanggan:</label>
                    <div class="d-flex gap-20 flex-wrap" style="padding:10px 14px;background:rgba(255,255,255,0.02);border-radius:8px;border:1px solid var(--border);">
                        <label class="d-flex align-center gap-6 cursor-pointer">
                            <input type="radio" name="customer_balance_alert_channel" value="whatsapp" {{ $settings['customer_balance_alert_channel'] === 'whatsapp' ? 'checked' : '' }}>
                            <span>💬 WhatsApp</span>
                        </label>
                        <label class="d-flex align-center gap-6 cursor-pointer">
                            <input type="radio" name="customer_balance_alert_channel" value="telegram" {{ $settings['customer_balance_alert_channel'] === 'telegram' ? 'checked' : '' }}>
                            <span>✈️ Telegram</span>
                        </label>
                        <label class="d-flex align-center gap-6 cursor-pointer">
                            <input type="radio" name="customer_balance_alert_channel" value="both" {{ $settings['customer_balance_alert_channel'] === 'both' ? 'checked' : '' }}>
                            <span style="color:#00d4ff;font-weight:600;">✨ Keduanya Aktif</span>
                        </label>
                    </div>
                </div>

                <div class="form-group" style="max-width:320px;">
                    <label class="form-label">Batas Ambang Saldo Minimum (Rp):</label>
                    <input type="number" name="customer_balance_threshold" value="{{ $settings['customer_balance_threshold'] }}" min="0" step="1000" class="form-control" placeholder="Contoh: 10000">
                    <small class="text-xs text-muted">Jika saldo pelanggan &le; nilai ini, notifikasi peringatan akan dikirimkan.</small>
                </div>
            </div>
        </div>

        {{-- Section 4: Personal Contact of Current User --}}
        <div class="card mb-24">
            <div class="card-header">
                <h3 style="font-size:16px;font-weight:600;color:var(--text-primary);display:flex;align-items:center;gap:8px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;color:#00d4ff;">
                        <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
                    </svg>
                    4. Kontak Notifikasi Saya ({{ $user->name }} - {{ ucfirst($user->roles->first()?->name ?? 'User') }})
                </h3>
                <p style="font-size:12px;color:var(--text-secondary);margin-top:2px;">
                    Isi kontak Anda agar notifikasi router online/offline langsung masuk ke nomor WhatsApp dan akun Telegram pribadi Anda
                </p>
            </div>
            <div style="padding:20px;">
                <div class="grid grid-2" style="gap:16px;">
                    <div class="form-group">
                        <label class="form-label">Nomor WhatsApp Pribadi Saya:</label>
                        <input type="text" name="my_phone" value="{{ $user->phone }}" class="form-control" placeholder="Contoh: 081234567890 atau 6281234567890">
                        <small class="text-xs text-muted">Bisa diawali 08... atau 62... (otomatis dinormalisasi ke format WhatsApp)</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Telegram Chat ID Pribadi Saya:</label>
                        <input type="text" name="my_telegram_chat_id" value="{{ $user->telegram_chat_id }}" class="form-control" placeholder="Contoh: 123456789">
                        <small class="text-xs text-muted">Dapatkan chat ID dari bot @userinfobot di Telegram.</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-end mb-32">
            <button type="submit" class="btn btn-primary" style="padding:10px 24px;font-size:14px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;margin-right:6px;"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Simpan Seluruh Pengaturan
            </button>
        </div>
    </form>
</div>

{{-- TAB 3: UJI COBA KIRIM --}}
<div id="tab-content-test" class="tab-pane {{ $activeTab === 'test' ? 'd-block' : 'd-none' }}">
    <div class="card" style="max-width:700px;margin:0 auto;">
        <div class="card-header">
            <h3 style="font-size:16px;font-weight:600;color:var(--text-primary);display:flex;align-items:center;gap:8px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;color:#00d4ff;">
                    <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                </svg>
                Uji Coba Kirim Notifikasi Langsung
            </h3>
            <p style="font-size:12px;color:var(--text-secondary);margin-top:2px;">
                Tes koneksi gateway untuk memastikan pesan WhatsApp atau Telegram dapat diterima dengan benar
            </p>
        </div>
        <form action="{{ route('notifications.test') }}" method="POST" style="padding:20px;">
            @csrf

            <div class="form-group mb-16">
                <label class="form-label" style="font-weight:600;">Pilih Saluran Uji Coba:</label>
                <div class="d-flex gap-16 flex-wrap" style="padding:10px 14px;background:rgba(255,255,255,0.02);border-radius:8px;border:1px solid var(--border);">
                    <label class="d-flex align-center gap-6 cursor-pointer">
                        <input type="radio" name="channel" value="whatsapp" checked onchange="updateTestInputs(this.value)">
                        <span>💬 WhatsApp</span>
                    </label>
                    <label class="d-flex align-center gap-6 cursor-pointer">
                        <input type="radio" name="channel" value="telegram" onchange="updateTestInputs(this.value)">
                        <span>✈️ Telegram</span>
                    </label>
                    <label class="d-flex align-center gap-6 cursor-pointer">
                        <input type="radio" name="channel" value="both" onchange="updateTestInputs(this.value)">
                        <span style="color:#00d4ff;font-weight:600;">✨ Keduanya</span>
                    </label>
                </div>
            </div>

            <div class="form-group mb-16" id="field-test-phone">
                <label class="form-label">Nomor WhatsApp Tujuan:</label>
                <input type="text" name="target_phone" value="{{ $user->phone ?: $settings['whatsapp_admin_phone'] }}" class="form-control" placeholder="Contoh: 081234567890 atau 6281234567890">
                <small class="text-xs text-muted">Bisa diawali 08... atau 62...</small>
            </div>

            <div class="form-group mb-24" id="field-test-telegram">
                <label class="form-label">Telegram Chat ID / Group ID Tujuan:</label>
                <input type="text" name="target_telegram_id" value="{{ $user->telegram_chat_id ?: $settings['telegram_admin_chat_id'] }}" class="form-control" placeholder="Contoh: 123456789 atau -100123456789">
                <small class="text-xs text-muted">Bisa berupa chat ID pribadi atau chat ID grup bot.</small>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;padding:10px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;margin-right:6px;"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                Kirim Pesan Uji Coba Sekarang
            </button>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
.tab-btn {
    background: transparent;
    color: var(--text-secondary);
    border: none;
    padding: 8px 16px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    transition: all 0.2s;
}

.tab-btn:hover {
    background: rgba(255, 255, 255, 0.05);
    color: var(--text-primary);
}

.tab-btn.active {
    background: rgba(0, 212, 255, 0.15);
    color: #00d4ff;
    font-weight: 600;
    border: 1px solid rgba(0, 212, 255, 0.3);
}

.d-none { display: none !important; }
.d-block { display: block !important; }
.cursor-pointer { cursor: pointer; }
</style>
@endpush

@push('scripts')
<script>
function switchTab(tabName) {
    document.querySelectorAll('.tab-pane').forEach(el => el.classList.remove('d-block'));
    document.querySelectorAll('.tab-pane').forEach(el => el.classList.add('d-none'));

    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));

    const content = document.getElementById('tab-content-' + tabName);
    if (content) {
        content.classList.remove('d-none');
        content.classList.add('d-block');
    }

    event.currentTarget.classList.add('active');

    // Update URL query string
    const url = new URL(window.location);
    url.searchParams.set('tab', tabName);
    window.history.replaceState({}, '', url);
}

function updateTestInputs(channel) {
    const phoneField = document.getElementById('field-test-phone');
    const tgField = document.getElementById('field-test-telegram');

    if (channel === 'whatsapp') {
        phoneField.style.display = 'block';
        tgField.style.display = 'none';
    } else if (channel === 'telegram') {
        phoneField.style.display = 'none';
        tgField.style.display = 'block';
    } else {
        phoneField.style.display = 'block';
        tgField.style.display = 'block';
    }
}
</script>
@endpush
