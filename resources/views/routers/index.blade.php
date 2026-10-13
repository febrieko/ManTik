@extends('layouts.app')

@section('title', 'Manajemen Router')
@section('page-title', 'Router')

@section('content')
<div class="page-header">
    <div class="page-header-title">
        <h2>Manajemen Router</h2>
        <p>Kelola semua perangkat MikroTik yang terhubung ke sistem</p>
    </div>
    <div class="d-flex gap-8">
        <a href="{{ route('router-status.index') }}" class="btn btn-outline" style="color:#00d4ff;border-color:rgba(0,212,255,0.4);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;margin-right:6px;"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            Status & Uptime
        </a>
        @can('create routers')
        <a href="{{ route('routers.create') }}" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
            Tambah Router
        </a>
        @endcan
    </div>
</div>

{{-- Stats Row --}}
<div class="grid grid-4 mb-24">
    <div class="stat-card cyan">
        <div class="stat-icon cyan">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12H3a9 9 0 0018 0h-2"/></svg>
        </div>
        <div class="stat-value">{{ $routers->count() }}</div>
        <div class="stat-label">Total Router</div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div class="stat-value">{{ $routers->where('status', 'online')->count() }}</div>
        <div class="stat-label">Online</div>
    </div>
    <div class="stat-card red">
        <div class="stat-icon red">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        </div>
        <div class="stat-value">{{ $routers->where('status', 'offline')->count() }}</div>
        <div class="stat-label">Offline</div>
    </div>
    <div class="stat-card yellow">
        <div class="stat-icon yellow">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        </div>
        <div class="stat-value">{{ $routers->where('status', 'unknown')->count() }}</div>
        <div class="stat-label">Unknown</div>
    </div>
</div>

{{-- Router Grid --}}
@if($routers->isEmpty())
<div class="card">
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M5 12H3a9 9 0 0018 0h-2M12 3v2m0 14v2"/>
        </svg>
        <h3>Belum ada router ditambahkan</h3>
        <p>Mulai dengan menambahkan router MikroTik pertama Anda</p>
        @can('create routers')
        <a href="{{ route('routers.create') }}" class="btn btn-primary" style="margin-top: 16px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
            Tambah Router Pertama
        </a>
        @endcan
    </div>
</div>
@else
<div class="grid grid-3">
    @foreach($routers as $router)
    <div class="card router-card" id="router-card-{{ $router->id }}">
        {{-- Status Badge --}}
        <div class="router-card-header">
            <div class="d-flex align-center gap-8">
                <div class="router-status-indicator">
                    <span class="status-dot {{ $router->status === 'online' ? 'online' : 'offline' }}"></span>
                </div>
                <div>
                    <div class="router-card-name">{{ $router->name }}</div>
                    <div class="router-card-host">{{ $router->host }}:{{ $router->port }}</div>
                </div>
            </div>
            <span class="badge {{ $router->status === 'online' ? 'badge-success' : ($router->status === 'offline' ? 'badge-danger' : 'badge-muted') }}">
                {{ ucfirst($router->status) }}
            </span>
        </div>

        {{-- Resource Stats --}}
        @if($router->latestStat)
        <div class="router-resources">
            <div class="resource-item">
                <div class="d-flex justify-between mb-4">
                    <span class="text-sm text-muted">CPU</span>
                    <span class="text-sm font-bold">{{ $router->latestStat->cpu_load }}%</span>
                </div>
                <div class="progress">
                    <div class="progress-bar {{ $router->latestStat->cpu_load > 80 ? 'red' : ($router->latestStat->cpu_load > 60 ? 'yellow' : 'cyan') }}"
                         style="width: {{ $router->latestStat->cpu_load }}%"></div>
                </div>
            </div>
            <div class="resource-item">
                <div class="d-flex justify-between mb-4">
                    <span class="text-sm text-muted">Memory</span>
                    <span class="text-sm font-bold">{{ $router->latestStat->memory_percent }}%</span>
                </div>
                <div class="progress">
                    <div class="progress-bar {{ $router->latestStat->memory_percent > 80 ? 'red' : ($router->latestStat->memory_percent > 60 ? 'yellow' : 'green') }}"
                         style="width: {{ $router->latestStat->memory_percent }}%"></div>
                </div>
            </div>
        </div>
        <div style="font-size: 11px; color: #4a5568; margin-bottom: 16px; padding: 8px; background: rgba(255,255,255,0.02); border-radius: 6px;">
            ⏱ Uptime: {{ $router->latestStat->uptime ?? 'N/A' }} &nbsp;|&nbsp;
            📡 v{{ $router->latestStat->version ?? '?' }}
        </div>
        @else
        <div style="font-size: 12px; color: #4a5568; text-align: center; padding: 16px; margin-bottom: 8px; background: rgba(255,255,255,0.02); border-radius: 6px;">
            Belum ada data resource
        </div>
        @endif

        {{-- Quick Stats --}}
        <div class="router-quick-stats">
            <div class="qs-item">
                <span class="qs-value">{{ $router->pppoe_users_count ?? 0 }}</span>
                <span class="qs-label">PPPoE</span>
            </div>
            <div class="qs-item">
                <span class="qs-value">{{ $router->hotspot_users_count ?? 0 }}</span>
                <span class="qs-label">Hotspot</span>
            </div>
            <div class="qs-item">
                <span class="qs-value">{{ $router->queues_count ?? 0 }}</span>
                <span class="qs-label">Queue</span>
            </div>
        </div>

        {{-- Info --}}
        @if($router->location || $router->model)
        <div style="font-size: 11px; color: #4a5568; margin-bottom: 16px;">
            @if($router->location) 📍 {{ $router->location }} @endif
            @if($router->model) &nbsp;| 🖥 {{ $router->brand }} {{ $router->model }} @endif
        </div>
        @endif

        <div style="font-size: 11px; margin-bottom: 12px; padding: 6px 10px; background: rgba(255,255,255,0.02); border-radius: 6px; display:flex; justify-content:space-between; align-items:center;">
            <span class="text-muted">Status: <strong style="color: {{ $router->status === 'online' ? '#00e676' : '#ff4444' }}">{{ ucfirst($router->status) }}</strong></span>
            <span style="color: {{ $router->status === 'online' ? '#00e676' : '#ff4444' }}; font-weight: 500;">
                ⏱ {{ $router->getCurrentStatusDurationFormatted() }}
            </span>
        </div>

        {{-- Actions --}}
        <div class="router-actions">
            <button class="btn btn-outline btn-sm" onclick="testConnection({{ $router->id }}, '{{ route('routers.test', $router->id) }}')" id="test-btn-{{ $router->id }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 006 8"/><path d="M5 12.55a11 11 0 0114.08 0"/></svg>
                Test
            </button>
            <a href="{{ route('router-status.show', $router) }}" class="btn btn-outline btn-sm" title="Riwayat Uptime">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Uptime
            </a>
            <a href="{{ route('routers.show', $router) }}" class="btn btn-outline btn-sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                Detail
            </a>
            @can('edit routers')
            <a href="{{ route('routers.edit', $router) }}" class="btn btn-outline btn-sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Edit
            </a>
            @endcan
            @can('delete routers')
            <form action="{{ route('routers.destroy', $router) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Hapus router {{ $router->name }}?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
                </button>
            </form>
            @endcan
        </div>
    </div>
    @endforeach
</div>
@endif
@endsection

@push('styles')
<style>
.router-card { display: flex; flex-direction: column; }
.router-card-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 16px; }
.router-card-name { font-size: 15px; font-weight: 700; }
.router-card-host { font-size: 11px; color: #4a5568; font-family: monospace; }
.router-resources { display: flex; flex-direction: column; gap: 10px; margin-bottom: 12px; }
.resource-item {}
.mb-4 { margin-bottom: 4px; }
.router-quick-stats { display: flex; gap: 8px; margin-bottom: 12px; }
.qs-item {
    flex: 1;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 8px;
    padding: 8px;
    text-align: center;
}
.qs-value { display: block; font-size: 18px; font-weight: 800; color: #00d4ff; }
.qs-label { font-size: 10px; color: #4a5568; text-transform: uppercase; letter-spacing: 0.5px; }
.router-actions { display: flex; gap: 6px; margin-top: auto; padding-top: 12px; border-top: 1px solid rgba(255,255,255,0.06); flex-wrap: wrap; }
</style>
@endpush

@push('scripts')
<script>
function testConnection(routerId, url) {
    const btn = document.getElementById(`test-btn-${routerId}`);
    const originalContent = btn.innerHTML;
    btn.innerHTML = '<div class="spinner" style="width:14px;height:14px; border:2px solid; border-radius:50%; border-right-color:transparent; animation:spin 1s linear infinite; display:inline-block; vertical-align:middle; margin-right:4px;"></div> Testing...';
    btn.disabled = true;

    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        }
    })
    .then(r => r.json())
    .then(data => {
        const card = document.getElementById(`router-card-${routerId}`);
        const badge = card.querySelector('.badge');
        const dot = card.querySelector('.status-dot');

        if (data.success) {
            badge.className = 'badge badge-success';
            badge.textContent = 'Online';
            dot.className = 'status-dot online';
            btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Online';
            btn.style.color = '#00e676';
        } else {
            badge.className = 'badge badge-danger';
            badge.textContent = 'Offline';
            dot.className = 'status-dot offline';
            btn.innerHTML = '✗ Offline';
            btn.style.color = '#ff4444';
        }
        
        setTimeout(() => {
            btn.innerHTML = originalContent;
            btn.style.color = '';
            btn.disabled = false;
        }, 3000);
    })
    .catch(() => {
        btn.innerHTML = originalContent;
        btn.disabled = false;
    });
}
</script>
@endpush
