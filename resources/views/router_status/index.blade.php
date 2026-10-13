@extends('layouts.app')

@section('title', 'Status & Uptime Router')
@section('page-title', 'Status & Uptime Router')

@section('content')
<div class="page-header">
    <div class="page-header-title">
        <h2>Monitoring Status & Uptime Router</h2>
        <p>Deteksi otomatis status online/offline, durasi aktif/mati, dan pencatatan riwayat downtime perangkat MikroTik</p>
    </div>
    <div class="d-flex gap-8 align-center">
        <form action="{{ route('router-status.check-all') }}" method="POST" id="form-scan-all">
            @csrf
            <button type="submit" class="btn btn-primary" id="btn-scan-all">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;margin-right:6px;">
                    <path d="M23 4v6h-6M1 20v-6h6"/>
                    <path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/>
                </svg>
                <span>Pindai Semua Router</span>
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

{{-- Summary Stats Cards --}}
<div class="grid grid-4 mb-24">
    <div class="stat-card cyan">
        <div class="stat-icon cyan">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
                <line x1="6" y1="6" x2="6.01" y2="6"></line>
                <line x1="6" y1="18" x2="6.01" y2="18"></line>
            </svg>
        </div>
        <div class="stat-value">{{ $totalRouters }}</div>
        <div class="stat-label">Total Router Diawasi</div>
    </div>

    <div class="stat-card green">
        <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
        </div>
        <div class="stat-value" style="color:#00e676;">{{ $onlineRouters }}</div>
        <div class="stat-label">Router Online</div>
    </div>

    <div class="stat-card red">
        <div class="stat-icon red">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
            </svg>
        </div>
        <div class="stat-value" style="color:#ff4444;">{{ $offlineRouters }}</div>
        <div class="stat-label">Router Offline</div>
    </div>

    <div class="stat-card blue">
        <div class="stat-icon blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
            </svg>
        </div>
        <div class="stat-value">{{ $avgUptimePercent }}%</div>
        <div class="stat-label">Rata-rata Uptime (30 Hari)</div>
    </div>
</div>

{{-- Live Router Status Cards --}}
<div class="card mb-24">
    <div class="card-header d-flex justify-between align-center">
        <div>
            <h3 style="font-size:16px;font-weight:600;color:var(--text-primary);display:flex;align-items:center;gap:8px;">
                <span class="status-indicator-live"></span>
                Status Router Real-Time
            </h3>
            <p style="font-size:12px;color:var(--text-secondary);margin-top:2px;">
                Status terdeteksi secara otomatis berkala melalui background scheduler
            </p>
        </div>
        <span class="text-sm text-muted">
            Total {{ $routers->count() }} perangkat
        </span>
    </div>

    <div style="padding: 18px 20px;">
        @if($routers->isEmpty())
            <div class="empty-state" style="padding:30px;text-align:center;">
                <p style="color:var(--text-secondary);">Tidak ada router yang terdaftar atau dapat diakses.</p>
            </div>
        @else
            <div class="grid grid-3" style="gap:16px;">
                @foreach($routers as $router)
                @php
                    $isOnline = $router->status === 'online';
                    $log = $router->currentUptimeLog ?? $router->latestUptimeLog;
                    $uptimePct = $router->getUptimePercentage(30);
                @endphp
                <div class="router-uptime-card {{ $isOnline ? 'is-online' : 'is-offline' }}" id="router-uptime-card-{{ $router->id }}">
                    {{-- Header --}}
                    <div class="d-flex justify-between align-center mb-12">
                        <div class="d-flex align-center gap-10">
                            <span class="status-pill-dot {{ $isOnline ? 'dot-online' : 'dot-offline' }}"></span>
                            <div>
                                <h4 style="font-size:15px;font-weight:600;margin:0;color:var(--text-primary);">{{ $router->name }}</h4>
                                <span style="font-size:12px;color:var(--text-secondary);font-family:monospace;">{{ $router->host }}:{{ $router->port }}</span>
                            </div>
                        </div>
                        <span class="badge {{ $isOnline ? 'badge-success' : 'badge-danger' }}" id="status-badge-{{ $router->id }}">
                            {{ strtoupper($router->status) }}
                        </span>
                    </div>

                    {{-- Status Details --}}
                    <div class="status-box mb-12 {{ $isOnline ? 'status-box-online' : 'status-box-offline' }}">
                        <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-muted);margin-bottom:2px;">
                            {{ $isOnline ? 'Status Aktif (Online)' : 'Status Mati (Offline)' }}
                        </div>
                        <div style="font-size:14px;font-weight:600;color:{{ $isOnline ? '#00e676' : '#ff4444' }};" id="duration-text-{{ $router->id }}">
                            {{ $isOnline ? 'Online selama' : 'Offline selama' }} {{ $router->getCurrentStatusDurationFormatted() }}
                        </div>
                        <div style="font-size:11px;color:var(--text-secondary);margin-top:4px;" id="started-text-{{ $router->id }}">
                            Sejak: {{ $log && $log->started_at ? $log->started_at->translatedFormat('d M Y, H:i:s') : ($router->last_checked_at ? $router->last_checked_at->translatedFormat('d M Y, H:i:s') : '-') }}
                        </div>
                    </div>

                    {{-- Metrics --}}
                    <div class="d-flex justify-between align-center mb-12" style="font-size:12px;background:rgba(255,255,255,0.02);padding:8px 12px;border-radius:8px;border:1px solid var(--border);">
                        <div>
                            <span class="text-muted">Latensi:</span>
                            <strong style="color:{{ $isOnline ? '#00d4ff' : 'var(--text-muted)' }};" id="latency-text-{{ $router->id }}">
                                {{ $log && $log->response_time_ms ? $log->response_time_ms . ' ms' : ($isOnline ? '< 100 ms' : 'Offline') }}
                            </strong>
                        </div>
                        <div>
                            <span class="text-muted">Uptime 30H:</span>
                            <strong style="color:#00d4ff;">{{ $uptimePct }}%</strong>
                        </div>
                    </div>

                    {{-- Uptime progress bar --}}
                    <div style="margin-bottom:14px;">
                        <div class="d-flex justify-between text-xs text-muted mb-4" style="font-size:11px;">
                            <span>Kestabilan Perangkat</span>
                            <span>{{ $uptimePct }}%</span>
                        </div>
                        <div class="progress" style="height:6px;background:rgba(255,255,255,0.06);border-radius:3px;">
                            <div class="progress-bar {{ $uptimePct >= 99 ? 'green' : ($uptimePct >= 90 ? 'cyan' : 'yellow') }}"
                                 style="width: {{ $uptimePct }}%;height:6px;border-radius:3px;"></div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="d-flex justify-between align-center pt-8" style="border-top:1px solid var(--border);">
                        <span class="text-xs text-muted" style="font-size:11px;" id="last-checked-{{ $router->id }}">
                            Dicek: {{ $router->last_checked_at ? $router->last_checked_at->diffForHumans() : 'Belum pernah' }}
                        </span>
                        <div class="d-flex gap-6">
                            <button type="button" 
                                    class="btn-sm btn-outline-cyan btn-ping-router" 
                                    data-router-id="{{ $router->id }}"
                                    title="Pindai koneksi router sekarang">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;margin-right:4px;">
                                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                                </svg>
                                <span>Cek</span>
                            </button>
                            <a href="{{ route('router-status.show', $router) }}" class="btn-sm btn-outline" title="Lihat riwayat lengkap">
                                Detail &rarr;
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

{{-- History / Event Logs Section --}}
<div class="card">
    <div class="card-header d-flex justify-between align-center">
        <div>
            <h3 style="font-size:16px;font-weight:600;color:var(--text-primary);display:flex;align-items:center;gap:8px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;color:#00d4ff;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                Rekaman Riwayat Online & Offline (Uptime & Downtime Logs)
            </h3>
            <p style="font-size:12px;color:var(--text-secondary);margin-top:2px;">
                Log mencatat kapan router online atau offline serta berapa lama durasinya berlangsung
            </p>
        </div>
    </div>

    {{-- Filter Form --}}
    <div style="padding: 16px 20px; background: rgba(255,255,255,0.02); border-bottom: 1px solid var(--border);">
        <form action="{{ route('router-status.index') }}" method="GET" class="d-flex gap-12 align-center flex-wrap">
            <div style="min-width: 180px;">
                <select name="router_id" class="form-control text-sm">
                    <option value="">-- Semua Router --</option>
                    @foreach($routers as $r)
                        <option value="{{ $r->id }}" {{ request('router_id') == $r->id ? 'selected' : '' }}>
                            {{ $r->name }} ({{ $r->host }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="min-width: 140px;">
                <select name="status" class="form-control text-sm">
                    <option value="">-- Semua Status --</option>
                    <option value="online" {{ request('status') === 'online' ? 'selected' : '' }}>🟢 Online Saja</option>
                    <option value="offline" {{ request('status') === 'offline' ? 'selected' : '' }}>🔴 Offline Saja</option>
                </select>
            </div>

            <div>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control text-sm" placeholder="Dari Tanggal">
            </div>

            <div>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control text-sm" placeholder="Sampai Tanggal">
            </div>

            <div style="flex: 1; min-width: 160px;">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control text-sm" placeholder="Cari nama router atau keterangan...">
            </div>

            <div class="d-flex gap-6">
                <button type="submit" class="btn btn-outline-primary text-sm" style="padding: 7px 14px;">
                    Filter
                </button>
                @if(request()->hasAny(['router_id', 'status', 'date_from', 'date_to', 'search']))
                <a href="{{ route('router-status.index') }}" class="btn btn-outline text-sm" style="padding: 7px 14px;">
                    Reset
                </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Logs Table --}}
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 220px;">Router</th>
                    <th style="width: 110px;">Status</th>
                    <th style="width: 170px;">Waktu Mulai</th>
                    <th style="width: 170px;">Waktu Berakhir</th>
                    <th style="width: 160px;">Durasi</th>
                    <th style="width: 100px;">Latensi</th>
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
                        <div class="d-flex align-center gap-8">
                            <span class="status-dot {{ $isLogOnline ? 'online' : 'offline' }}"></span>
                            <div>
                                <a href="{{ route('router-status.show', $log->router_id) }}" style="color:var(--text-primary);font-weight:600;text-decoration:none;">
                                    {{ $log->router->name ?? 'Router #' . $log->router_id }}
                                </a>
                                <div class="text-xs text-muted">{{ $log->router->host ?? '-' }}:{{ $log->router->port ?? '-' }}</div>
                            </div>
                        </div>
                    </td>
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
                    <td colspan="7" class="text-center" style="padding: 40px; color: var(--text-muted);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:40px;height:40px;margin-bottom:10px;opacity:0.5;">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <div>Belum ada rekaman riwayat status yang sesuai dengan filter.</div>
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

@push('styles')
<style>
.status-indicator-live {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: #00e676;
    box-shadow: 0 0 10px #00e676;
    animation: pulseLive 2s infinite;
}

@keyframes pulseLive {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(0, 230, 118, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(0, 230, 118, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(0, 230, 118, 0); }
}

.router-uptime-card {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 16px;
    transition: all 0.25s ease;
}

.router-uptime-card:hover {
    transform: translateY(-2px);
    background: rgba(255, 255, 255, 0.05);
    border-color: rgba(0, 212, 255, 0.3);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
}

.router-uptime-card.is-offline {
    border-color: rgba(255, 68, 68, 0.3);
    background: rgba(255, 68, 68, 0.02);
}

.status-pill-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
}

.dot-online {
    background: #00e676;
    box-shadow: 0 0 8px #00e676;
}

.dot-offline {
    background: #ff4444;
    box-shadow: 0 0 8px #ff4444;
}

.status-box {
    padding: 10px 12px;
    border-radius: 8px;
}

.status-box-online {
    background: rgba(0, 230, 118, 0.06);
    border: 1px solid rgba(0, 230, 118, 0.15);
}

.status-box-offline {
    background: rgba(255, 68, 68, 0.06);
    border: 1px solid rgba(255, 68, 68, 0.15);
}

.btn-outline-cyan {
    background: transparent;
    color: #00d4ff;
    border: 1px solid rgba(0, 212, 255, 0.4);
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    transition: all 0.2s;
}

.btn-outline-cyan:hover {
    background: rgba(0, 212, 255, 0.15);
    border-color: #00d4ff;
    color: #fff;
}

.btn-outline {
    background: transparent;
    color: var(--text-secondary);
    border: 1px solid var(--border);
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    transition: all 0.2s;
}

.btn-outline:hover {
    background: rgba(255, 255, 255, 0.05);
    color: var(--text-primary);
}

.spin-animation {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    100% { transform: rotate(360deg); }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // Individual router ping
    const pingButtons = document.querySelectorAll('.btn-ping-router');
    pingButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const routerId = this.getAttribute('data-router-id');
            const originalHtml = this.innerHTML;
            
            this.disabled = true;
            this.innerHTML = `
                <svg class="spin-animation" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;margin-right:4px;">
                    <line x1="12" y1="2" x2="12" y2="6"></line>
                    <line x1="12" y1="18" x2="12" y2="22"></line>
                    <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line>
                    <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line>
                    <line x1="2" y1="12" x2="6" y2="12"></line>
                    <line x1="18" y1="12" x2="22" y2="12"></line>
                    <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line>
                    <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line>
                </svg>
                <span>Cek...</span>
            `;

            fetch(`/router-status/${routerId}/check`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.data) {
                    const r = data.data;
                    const card = document.getElementById(`router-uptime-card-${routerId}`);
                    const badge = document.getElementById(`status-badge-${routerId}`);
                    const durationText = document.getElementById(`duration-text-${routerId}`);
                    const latencyText = document.getElementById(`latency-text-${routerId}`);
                    const lastChecked = document.getElementById(`last-checked-${routerId}`);

                    if (r.status === 'online') {
                        card.classList.remove('is-offline');
                        card.classList.add('is-online');
                        badge.className = 'badge badge-success';
                        badge.innerText = 'ONLINE';
                        durationText.style.color = '#00e676';
                        durationText.innerText = 'Online selama ' + r.duration;
                        latencyText.innerText = r.response_time_ms ? r.response_time_ms + ' ms' : '< 100 ms';
                        latencyText.style.color = '#00d4ff';
                    } else {
                        card.classList.remove('is-online');
                        card.classList.add('is-offline');
                        badge.className = 'badge badge-danger';
                        badge.innerText = 'OFFLINE';
                        durationText.style.color = '#ff4444';
                        durationText.innerText = 'Offline selama ' + r.duration;
                        latencyText.innerText = 'Offline';
                        latencyText.style.color = 'var(--text-muted)';
                    }

                    if (lastChecked) {
                        lastChecked.innerText = 'Dicek: baru saja';
                    }

                    // Reload page after brief delay to update logs table if status changed
                    if (r.status_changed) {
                        setTimeout(() => window.location.reload(), 800);
                    }
                }
            })
            .catch(err => {
                console.error(err);
                alert('Gagal memeriksa router: Terjadi kesalahan jaringan.');
            })
            .finally(() => {
                this.disabled = false;
                this.innerHTML = originalHtml;
            });
        });
    });

    // Scan all routers button loading state
    const formScanAll = document.getElementById('form-scan-all');
    const btnScanAll = document.getElementById('btn-scan-all');
    if (formScanAll && btnScanAll) {
        formScanAll.addEventListener('submit', function () {
            btnScanAll.disabled = true;
            btnScanAll.innerHTML = `
                <svg class="spin-animation" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;margin-right:6px;">
                    <line x1="12" y1="2" x2="12" y2="6"></line>
                    <line x1="12" y1="18" x2="12" y2="22"></line>
                    <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line>
                    <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line>
                    <line x1="2" y1="12" x2="6" y2="12"></line>
                    <line x1="18" y1="12" x2="22" y2="12"></line>
                    <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line>
                    <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line>
                </svg>
                <span>Memindai Jaringan...</span>
            `;
        });
    }
});
</script>
@endpush
