@extends('layouts.app')

@section('title', 'Detail Status - ' . $router->name)
@section('page-title', 'Detail Status Router')

@section('content')
<div class="page-header">
    <div class="page-header-title">
        <div class="d-flex align-center gap-8 mb-4">
            <a href="{{ route('router-status.index') }}" class="btn-sm btn-outline" style="text-decoration:none;">
                &larr; Kembali ke Daftar Status
            </a>
            <span class="badge {{ $router->status === 'online' ? 'badge-success' : 'badge-danger' }}" style="font-size:12px;">
                {{ strtoupper($router->status) }}
            </span>
        </div>
        <h2>{{ $router->name }} &mdash; Riwayat Uptime & Downtime</h2>
        <p>{{ $router->host }}:{{ $router->port }} &bull; {{ $router->brand ?? 'MikroTik' }} {{ $router->model ?? '' }} &bull; {{ $router->location ?? 'Lokasi tidak ditentukan' }}</p>
    </div>
    <div class="d-flex gap-8 align-center">
        <form action="{{ route('router-status.check', $router) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;margin-right:6px;">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
                Pindai Status Sekarang
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

{{-- Router Live Banner --}}
@php
    $isOnline = $router->status === 'online';
    $curLog = $router->currentUptimeLog ?? $router->latestUptimeLog;
@endphp
<div class="card mb-24" style="padding: 20px; border-left: 4px solid {{ $isOnline ? '#00e676' : '#ff4444' }};">
    <div class="d-flex justify-between align-center flex-wrap gap-16">
        <div>
            <div class="d-flex align-center gap-8 mb-4">
                <span class="status-dot {{ $isOnline ? 'online' : 'offline' }}"></span>
                <span style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted);">
                    Status Berjalan Saat Ini
                </span>
            </div>
            <div style="font-size: 20px; font-weight: 700; color: {{ $isOnline ? '#00e676' : '#ff4444' }};">
                {{ $isOnline ? 'Router Online' : 'Router Offline' }} selama {{ $router->getCurrentStatusDurationFormatted() }}
            </div>
            <div style="font-size: 12px; color: var(--text-secondary); margin-top: 4px;">
                Mulai sejak: {{ $curLog && $curLog->started_at ? $curLog->started_at->translatedFormat('l, d F Y - H:i:s') : '-' }}
                &bull; Terakhir diperiksa: {{ $router->last_checked_at ? $router->last_checked_at->translatedFormat('d M Y H:i:s') : 'Belum pernah' }}
            </div>
        </div>
        <div class="text-right">
            <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 4px;">Diagnostik Terakhir</div>
            <div style="font-size: 13px; color: var(--text-primary); max-width: 400px;">
                {{ $curLog->reason ?? 'Tidak ada pesan' }}
            </div>
        </div>
    </div>
</div>

{{-- Metrics Row --}}
<div class="grid grid-4 mb-24">
    <div class="stat-card cyan">
        <div class="stat-icon cyan">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="stat-value">{{ $uptime24h }}%</div>
        <div class="stat-label">Uptime 24 Jam Terakhir</div>
    </div>

    <div class="stat-card green">
        <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        </div>
        <div class="stat-value" style="color:#00e676;">{{ $uptime7d }}%</div>
        <div class="stat-label">Uptime 7 Hari Terakhir</div>
    </div>

    <div class="stat-card blue">
        <div class="stat-icon blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
        <div class="stat-value">{{ $uptime30d }}%</div>
        <div class="stat-label">Uptime 30 Hari Terakhir</div>
    </div>

    <div class="stat-card red">
        <div class="stat-icon red">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        </div>
        <div class="stat-value" style="color:#ff4444;">{{ $outagesCount30d }}x</div>
        <div class="stat-label">Insiden Mati (30 Hari) &bull; {{ $totalDowntimeFormatted }}</div>
    </div>
</div>

{{-- History Table --}}
<div class="card">
    <div class="card-header d-flex justify-between align-center">
        <div>
            <h3 style="font-size:16px;font-weight:600;color:var(--text-primary);">
                Riwayat Lengkap Status Perangkat
            </h3>
            <p style="font-size:12px;color:var(--text-secondary);margin-top:2px;">
                Daftar semua kejadian perpindahan status online/offline beserta durasi setiap sesi
            </p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 120px;">Status</th>
                    <th style="width: 190px;">Mulai Status</th>
                    <th style="width: 190px;">Selesai Status</th>
                    <th style="width: 180px;">Durasi Sesi</th>
                    <th style="width: 110px;">Latensi</th>
                    <th>Keterangan / Diagnostik</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                @php
                    $isLogOnline = $log->status === 'online';
                @endphp
                <tr>
                    <td>
                        <span class="badge {{ $isLogOnline ? 'badge-success' : 'badge-danger' }}">
                            {{ strtoupper($log->status) }}
                        </span>
                    </td>
                    <td>
                        <div style="font-weight:500;">{{ $log->started_at->translatedFormat('d M Y') }}</div>
                        <div class="text-xs text-muted">{{ $log->started_at->format('H:i:s') }}</div>
                    </td>
                    <td>
                        @if($log->isOngoing())
                            <span class="badge" style="background:rgba(0,212,255,0.15);color:#00d4ff;border:1px solid rgba(0,212,255,0.3);">
                                Sedang Berlangsung
                            </span>
                        @else
                            <div style="font-weight:500;">{{ $log->ended_at->translatedFormat('d M Y') }}</div>
                            <div class="text-xs text-muted">{{ $log->ended_at->format('H:i:s') }}</div>
                        @endif
                    </td>
                    <td>
                        <div style="font-weight:600;color:{{ $isLogOnline ? '#00e676' : '#ff4444' }};">
                            {{ $log->duration_formatted }}
                        </div>
                        @if($log->isOngoing())
                            <span class="text-xs text-muted">(berjalan saat ini)</span>
                        @endif
                    </td>
                    <td>
                        @if($log->response_time_ms)
                            <span style="font-family:monospace;color:#00d4ff;">{{ $log->response_time_ms }} ms</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        <span class="text-sm" style="color:{{ $isLogOnline ? 'var(--text-secondary)' : '#ff8080' }};">
                            {{ $log->reason ?? '-' }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 40px; color: var(--text-muted);">
                        Belum ada rekaman riwayat untuk router ini.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
    <div style="padding: 16px 20px; border-top: 1px solid var(--border);">
        {{ $logs->links() }}
    </div>
    @endif
</div>
@endsection
