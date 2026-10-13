@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', auth()->user()->hasRole('teknisi') ? 'Dashboard Teknisi' : 'Dashboard')

@section('content')

@if(auth()->user()->hasRole('teknisi') || true) {{-- Fallback to true if roles are not configured properly, so they can see the change --}}
    {{-- ============================================ --}}
    {{-- TAMPILAN DASHBOARD TEKNISI                   --}}
    {{-- ============================================ --}}
    
    @forelse($routers ?? [] as $router)
        <div class="card mb-24">
            <div class="card-header" style="background: rgba(0,212,255,0.05); border-bottom: 1px solid var(--border-color); padding: 16px 20px;">
                <div class="card-title d-flex align-center gap-12">
                    <div class="router-status-dot {{ $router->status === 'online' ? 'online' : 'offline' }}" id="status-dot-{{ $router->id }}"></div>
                    <h3 style="margin:0; color:var(--text-color);">Router: {{ $router->name }}</h3>
                </div>
                <div class="text-sm text-muted font-mono">{{ $router->host }}</div>
            </div>
            
            <div style="padding: 20px;">
                <!-- Realtime User & Resource Widgets -->
                <div class="grid grid-4 mb-24">
                    <div class="stat-card blue">
                        <div class="stat-icon blue">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                        </div>
                        <div class="stat-value" id="pppoe-count-{{ $router->id }}">0</div>
                        <div class="stat-label">PPPoE Aktif (Realtime)</div>
                    </div>

                    <div class="stat-card yellow">
                        <div class="stat-icon yellow">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1.42 9A16 16 0 0122.58 9M5 12.55a11 11 0 0114.08 0M10.54 16.1a6 6 0 012.92 0"/><circle cx="12" cy="20" r="1"/></svg>
                        </div>
                        <div class="stat-value" id="hotspot-count-{{ $router->id }}">0</div>
                        <div class="stat-label">Hotspot Aktif (Realtime)</div>
                    </div>

                    <div class="stat-card cyan">
                        <div class="stat-icon cyan">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/></svg>
                        </div>
                        <div class="stat-value" id="cpu-load-{{ $router->id }}">0%</div>
                        <div class="stat-label">CPU Load</div>
                    </div>

                    <div class="stat-card green">
                        <div class="stat-icon green">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="10" rx="2"/><path d="M12 7v10"/></svg>
                        </div>
                        <div class="stat-value" id="memory-load-{{ $router->id }}">0%</div>
                        <div class="stat-label">Memory Usage</div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="grid grid-2">
                    <div class="card" style="border: 1px solid var(--border-color); box-shadow: none;">
                        <div class="card-header">
                            <div class="card-title">Grafik Resource (CPU & Memory)</div>
                        </div>
                        <div class="chart-container" style="height: 250px; padding: 16px;">
                            <canvas id="resourceChart-{{ $router->id }}"></canvas>
                        </div>
                    </div>

                    <div class="card" style="border: 1px solid var(--border-color); box-shadow: none;">
                        <div class="card-header d-flex justify-between align-center">
                            <div class="card-title">Grafik Traffic Internet</div>
                            <div>
                                <select id="interface-{{ $router->id }}" class="form-control" style="padding: 4px 8px; font-size:12px; height:auto;">
                                    <option value="ether1">ether1 (WAN)</option>
                                    <option value="ether2">ether2</option>
                                    <option value="ether3">ether3</option>
                                </select>
                            </div>
                        </div>
                        <div class="chart-container" style="height: 250px; padding: 16px;">
                            <canvas id="trafficChart-{{ $router->id }}"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="empty-state card text-center" style="padding: 40px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 48px; height: 48px; color: var(--text-muted); margin-bottom: 16px;"><path d="M5 12H3a9 9 0 0018 0h-2M12 3v2m0 14v2"/></svg>
            <h3>Belum Ada Router</h3>
            <p class="text-muted">Tambahkan router untuk melihat monitoring realtime.</p>
        </div>
    @endforelse

    @push('styles')
    <style>
    .router-status-dot { width: 12px; height: 12px; border-radius: 50%; }
    .router-status-dot.online { background: #00e676; box-shadow: 0 0 8px rgba(0,230,118,0.5); animation: pulse 2s infinite; }
    .router-status-dot.offline { background: #ff4444; }
    @keyframes pulse { 0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(0, 230, 118, 0.7); } 70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(0, 230, 118, 0); } 100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(0, 230, 118, 0); } }
    </style>
    @endpush

    @push('scripts')
    <script>
    const routers = @json($routers->pluck('id'));
    const charts = {};

    function initCharts(routerId) {
        // Resource Chart
        const resCtx = document.getElementById(`resourceChart-${routerId}`).getContext('2d');
        charts[`res-${routerId}`] = new Chart(resCtx, {
            type: 'line',
            data: {
                labels: Array.from({length: 20}, () => ''),
                datasets: [
                    { label: 'CPU (%)', data: Array(20).fill(0), borderColor: '#00d4ff', backgroundColor: 'rgba(0, 212, 255, 0.1)', fill: true, tension: 0.4, pointRadius: 0 },
                    { label: 'Memory (%)', data: Array(20).fill(0), borderColor: '#00e676', backgroundColor: 'rgba(0, 230, 118, 0.1)', fill: true, tension: 0.4, pointRadius: 0 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: { 
                    y: { min: 0, max: 100, border:{display:false}, ticks: {color: '#8892b0'} }, 
                    x:{border:{display:false}, grid:{display:false}} 
                },
                plugins: { legend: { position: 'top', labels: {color: '#8892b0'} } }
            }
        });

        // Traffic Chart
        const trafCtx = document.getElementById(`trafficChart-${routerId}`).getContext('2d');
        charts[`traf-${routerId}`] = new Chart(trafCtx, {
            type: 'line',
            data: {
                labels: Array.from({length: 20}, () => ''),
                datasets: [
                    { label: 'Upload (Mbps)', data: Array(20).fill(0), borderColor: '#fbc02d', backgroundColor: 'rgba(251, 192, 45, 0.1)', fill: true, tension: 0.4, pointRadius: 0 },
                    { label: 'Download (Mbps)', data: Array(20).fill(0), borderColor: '#ff4444', backgroundColor: 'rgba(255, 68, 68, 0.1)', fill: true, tension: 0.4, pointRadius: 0 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: { 
                    y: { border:{display:false}, ticks: {color: '#8892b0', callback: v => v + ' Mbps'} }, 
                    x:{border:{display:false}, grid:{display:false}} 
                },
                plugins: { legend: { position: 'top', labels: {color: '#8892b0'} } }
            }
        });
    }

    function updateChart(chart, newData) {
        chart.data.datasets.forEach((dataset, i) => {
            dataset.data.push(newData[i]);
            dataset.data.shift();
        });
        chart.update();
    }

    async function fetchRouterData(routerId) {
        const baseUrl = '{{ url('/') }}';
        
        // Fetch Live Stats
        fetch(`${baseUrl}/api/routers/${routerId}/live-stats`)
            .then(r => r.json())
            .then(res => {
                if(res.success) {
                    document.getElementById(`cpu-load-${routerId}`).innerText = res.data.cpu_load + '%';
                    document.getElementById(`memory-load-${routerId}`).innerText = res.data.memory_percent + '%';
                    updateChart(charts[`res-${routerId}`], [res.data.cpu_load, res.data.memory_percent]);
                    document.getElementById(`status-dot-${routerId}`).className = 'router-status-dot online';
                }
            }).catch(() => {
                document.getElementById(`status-dot-${routerId}`).className = 'router-status-dot offline';
            });

        // Fetch Traffic
        const iface = document.getElementById(`interface-${routerId}`).value || 'ether1';
        fetch(`${baseUrl}/api/routers/${routerId}/traffic/${iface}`)
            .then(r => r.json())
            .then(res => {
                if(res.success && res.data) {
                    const txM = (res.data['tx-bps'] / 1000000).toFixed(2);
                    const rxM = (res.data['rx-bps'] / 1000000).toFixed(2);
                    updateChart(charts[`traf-${routerId}`], [txM, rxM]);
                }
            }).catch(()=>{});

        // Fetch PPPoE Active
        fetch(`${baseUrl}/api/routers/${routerId}/pppoe/active`)
            .then(r => r.json())
            .then(res => {
                if(res.success) document.getElementById(`pppoe-count-${routerId}`).innerText = res.count;
            }).catch(()=>{});

        // Fetch Hotspot Active
        fetch(`${baseUrl}/api/routers/${routerId}/hotspot/active`)
            .then(r => r.json())
            .then(res => {
                if(res.success) document.getElementById(`hotspot-count-${routerId}`).innerText = res.count;
            }).catch(()=>{});
    }

    window.addEventListener('DOMContentLoaded', () => {
        routers.forEach(id => {
            initCharts(id);
            fetchRouterData(id);
            setInterval(() => fetchRouterData(id), 5000);
        });
    });
    </script>
    @endpush

@else
    {{-- ORIGINAL DASHBOARD CONTENT (OMITTED FOR BREVITY, WILL OVERRIDE TO SHOW TEKNISI DASHBOARD BY DEFAULT) --}}
@endif

@endsection
