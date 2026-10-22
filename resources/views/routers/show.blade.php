@extends('layouts.app')
@section('title', $router->name)
@section('page-title', $router->name)

@section('content')
<div class="page-header">
    <div class="page-header-title">
        <h2>{{ $router->name }}</h2>
        <p>
            {{ $router->host }}:{{ $router->port }} &mdash; {{ $router->location ?? 'Tidak ada lokasi' }}
            @if($router->ssid)
                &nbsp;|&nbsp; <span style="color:#00d4ff;font-weight:600;">📶 SSID: {{ $router->ssid }}</span>
            @endif
        </p>
    </div>
    <div class="d-flex gap-8">
        <a href="{{ route('router-status.show', $router) }}" class="btn btn-outline" style="color:#00d4ff;border-color:rgba(0,212,255,0.4);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;margin-right:4px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Riwayat Uptime
        </a>
        <a href="{{ route('routers.edit', $router) }}" class="btn btn-outline">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Edit
        </a>
        <a href="{{ route('routers.index') }}" class="btn btn-outline">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Kembali
        </a>
    </div>
</div>

@if($router->latestStat)
{{-- Resource Cards --}}
<div class="grid grid-4 mb-24">
    <div class="stat-card cyan">
        <div class="stat-icon cyan">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/></svg>
        </div>
        <div class="stat-value">{{ $router->latestStat->cpu_load }}%</div>
        <div class="stat-label">CPU Load</div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
        </div>
        <div class="stat-value">{{ $router->latestStat->memory_percent }}%</div>
        <div class="stat-label">Memory Used</div>
    </div>
    <div class="stat-card yellow">
        <div class="stat-icon yellow">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="stat-value" style="font-size: 16px;">{{ $router->latestStat->uptime ?? 'N/A' }}</div>
        <div class="stat-label">Uptime</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/></svg>
        </div>
        <div class="stat-value">{{ $router->pppoeUsers->count() }}</div>
        <div class="stat-label">PPPoE Users</div>
    </div>
</div>
@endif

<div class="grid grid-2">
    {{-- Router Info --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">Informasi Router</div>
            <span class="badge {{ $router->status === 'online' ? 'badge-success' : 'badge-danger' }}">{{ ucfirst($router->status) }}</span>
        </div>
        <table>
            <tbody>
                <tr><td style="color: #4a5568; width: 140px;">Nama</td><td class="font-bold">{{ $router->name }}</td></tr>
                <tr><td style="color: #4a5568;">IP Address</td><td class="font-mono">{{ $router->host }}</td></tr>
                <tr><td style="color: #4a5568;">Port API</td><td class="font-mono">{{ $router->port }}</td></tr>
                <tr><td style="color: #4a5568;">Username</td><td class="font-mono">{{ $router->username }}</td></tr>
                <tr><td style="color: #4a5568;">Lokasi</td><td>{{ $router->location ?? '-' }}</td></tr>
                <tr><td style="color: #4a5568;">Model</td><td>{{ $router->brand }} {{ $router->model ?? '-' }}</td></tr>
                <tr><td style="color: #4a5568;">Deskripsi</td><td>{{ $router->description ?? '-' }}</td></tr>
                <tr><td style="color: #4a5568;">Terakhir Dicek</td><td>{{ $router->last_checked_at?->diffForHumans() ?? 'Belum pernah' }}</td></tr>
            </tbody>
        </table>
    </div>

    {{-- CPU/RAM Chart --}}
    <div class="card">
        <div class="card-header"><div class="card-title">CPU & Memory (5 menit)</div></div>
        <div class="chart-container">
            <canvas id="resourceChart"></canvas>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Initial Data from DB (Last 5 mins)
const initialStats = @json($stats);
const maxPoints = 30; // approx 1.5 mins if polling every 3 seconds

let labels = initialStats.map(s => new Date(s.recorded_at).toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit', second:'2-digit'}));
let cpuData = initialStats.map(s => s.cpu_load);
let memData = initialStats.map(s => s.memory_used && s.memory_total ? (s.memory_used/s.memory_total*100).toFixed(1) : 0);

// If no historical data, fill with empty
if (labels.length === 0) {
    labels = Array(maxPoints).fill('');
    cpuData = Array(maxPoints).fill(0);
    memData = Array(maxPoints).fill(0);
} else {
    // Trim to maxPoints if needed
    if (labels.length > maxPoints) {
        labels = labels.slice(-maxPoints);
        cpuData = cpuData.slice(-maxPoints);
        memData = memData.slice(-maxPoints);
    }
}

const ctx = document.getElementById('resourceChart').getContext('2d');
const resourceChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [
            { label: 'CPU %', data: cpuData, borderColor: '#00d4ff', backgroundColor: 'rgba(0,212,255,0.06)', borderWidth: 2, fill: true, tension: 0.4, pointRadius: 0, pointHoverRadius: 4 },
            { label: 'RAM %', data: memData, borderColor: '#00e676', backgroundColor: 'rgba(0,230,118,0.04)', borderWidth: 2, fill: true, tension: 0.4, pointRadius: 0, pointHoverRadius: 4 }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        animation: { duration: 500, easing: 'linear' },
        interaction: { intersect: false, mode: 'index' },
        plugins: { 
            legend: { labels: { color: '#8892b0', font: { size: 11 } } }, 
            tooltip: { backgroundColor: 'rgba(13,20,36,0.9)', borderColor: 'rgba(255,255,255,0.1)', borderWidth: 1, titleColor: '#8892b0', bodyColor: '#e8eaf6' } 
        },
        scales: {
            x: { grid: { color: 'rgba(255,255,255,0.02)' }, ticks: { display: false }, border: { display: false } },
            y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#4a5568', font: { size: 10 }, callback: v => v + '%' }, border: { display: false }, max: 100, min: 0 }
        }
    }
});

// Live Polling
const routerId = '{{ $router->id }}';
const baseUrl = '{{ url("api/routers") }}';
setInterval(() => {
    fetch(`${baseUrl}/${routerId}/live-stats`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(json => {
        if (json.success && json.data) {
            const timeLabel = new Date().toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit', second:'2-digit'});
            
            // Push new data
            resourceChart.data.labels.push(timeLabel);
            resourceChart.data.datasets[0].data.push(json.data.cpu_load);
            resourceChart.data.datasets[1].data.push(json.data.memory_percent);
            
            // Shift if exceeding maxPoints
            if (resourceChart.data.labels.length > maxPoints) {
                resourceChart.data.labels.shift();
                resourceChart.data.datasets[0].data.shift();
                resourceChart.data.datasets[1].data.shift();
            }
            resourceChart.update('none');

            // Update DOM text if we had IDs for them (Optional, but we just focus on graph for now)
        }
    })
    .catch(err => console.error("Polling error:", err));
}, 3000);
</script>
@endpush
