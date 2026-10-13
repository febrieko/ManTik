@extends('layouts.app')

@section('title', 'MRTG Traffic Monitor')
@section('page-title', 'MRTG Traffic Monitor')

@push('styles')
<style>
    .mrtg-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 16px;
    }
    .mrtg-router-selector {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    .mrtg-router-selector select {
        min-width: 220px;
    }
    .mrtg-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        background: rgba(0, 230, 118, 0.12);
        color: #00e676;
        border: 1px solid rgba(0, 230, 118, 0.25);
    }
    .mrtg-badge.offline {
        background: rgba(255, 80, 80, 0.12);
        color: #ff5050;
        border-color: rgba(255, 80, 80, 0.25);
    }
    .mrtg-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(480px, 1fr));
        gap: 20px;
    }
    .mrtg-card {
        background: var(--card-bg, #111827);
        border: 1px solid rgba(255,255,255,0.07);
        border-radius: 16px;
        overflow: hidden;
        transition: border-color 0.25s, box-shadow 0.25s, transform 0.2s;
        cursor: pointer;
        text-decoration: none;
        display: block;
    }
    .mrtg-card:hover {
        border-color: rgba(0, 212, 255, 0.4);
        box-shadow: 0 8px 32px rgba(0, 212, 255, 0.1);
        transform: translateY(-2px);
    }
    .mrtg-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 18px;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        background: rgba(255,255,255,0.02);
    }
    .mrtg-iface-name {
        font-size: 14px;
        font-weight: 700;
        color: #e8eaf6;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .mrtg-iface-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #00e676;
        box-shadow: 0 0 6px #00e676;
        animation: pulse-dot 2s infinite;
    }
    .mrtg-iface-dot.inactive {
        background: #4a5568;
        box-shadow: none;
        animation: none;
    }
    @keyframes pulse-dot {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.4; }
    }
    .mrtg-last-seen {
        font-size: 11px;
        color: #4a5568;
    }
    .mrtg-chart-area {
        padding: 12px 16px;
        height: 100px;
        position: relative;
    }
    .mrtg-stats-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1px;
        background: rgba(255,255,255,0.04);
        border-top: 1px solid rgba(255,255,255,0.04);
    }
    .mrtg-stat-cell {
        padding: 10px 14px;
        background: var(--card-bg, #111827);
        text-align: center;
    }
    .mrtg-stat-label {
        font-size: 10px;
        color: #4a5568;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }
    .mrtg-stat-value {
        font-size: 14px;
        font-weight: 700;
        font-family: 'Courier New', monospace;
    }
    .mrtg-stat-value.rx { color: #00e676; }
    .mrtg-stat-value.tx { color: #00d4ff; }
    .mrtg-stat-value.peak { color: #ffd740; }
    .mrtg-stat-unit {
        font-size: 10px;
        color: #4a5568;
        font-weight: 400;
    }
    .mrtg-empty {
        grid-column: 1 / -1;
        text-align: center;
        padding: 60px 20px;
        color: #4a5568;
    }
    .mrtg-empty svg {
        width: 56px;
        height: 56px;
        margin: 0 auto 16px;
        display: block;
        opacity: 0.4;
    }
    .mrtg-toolbar {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .mrtg-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        border: 1px solid rgba(255,255,255,0.1);
        background: rgba(255,255,255,0.04);
        color: #8892b0;
        text-decoration: none;
        transition: all 0.2s;
        cursor: pointer;
    }
    .mrtg-btn:hover {
        background: rgba(0, 212, 255, 0.1);
        border-color: rgba(0, 212, 255, 0.3);
        color: #00d4ff;
    }
    .mrtg-btn.active {
        background: rgba(0, 212, 255, 0.15);
        border-color: rgba(0, 212, 255, 0.4);
        color: #00d4ff;
    }
    .legend-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
    }
    .no-router-state {
        text-align: center;
        padding: 80px 20px;
    }
    .no-router-state svg {
        width: 64px;
        height: 64px;
        margin: 0 auto 20px;
        display: block;
        opacity: 0.3;
    }
    .refresh-indicator {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: #4a5568;
    }
    .refresh-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #00e676;
        animation: pulse-dot 1.5s infinite;
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <div class="page-header-title">
        <h2>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:22px;height:22px;display:inline-block;vertical-align:middle;margin-right:8px;">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
            </svg>
            MRTG Traffic Monitor
        </h2>
        <p>Monitoring trafik internet semua interface router secara real-time</p>
    </div>
</div>

{{-- Router Selector Bar --}}
<div class="card mb-24">
    <div style="padding: 16px 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
        <form method="GET" action="{{ route('traffic.mrtg') }}" id="routerForm" style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;color:#8892b0;">
                    <path d="M5 12H3a9 9 0 0018 0h-2M12 3v2m0 14v2"/>
                </svg>
                <label style="font-size:13px;color:#8892b0;font-weight:600;">Router:</label>
            </div>
            <select name="router_id" id="router_id" class="form-control" style="min-width:220px;" onchange="this.form.submit()">
                <option value="">-- Pilih Router --</option>
                @foreach($routers as $r)
                <option value="{{ $r->id }}" {{ $selectedRouterId == $r->id ? 'selected' : '' }}>
                    {{ $r->name }}
                    @if($r->location) ({{ $r->location }}) @endif
                </option>
                @endforeach
            </select>

            @if($selectedRouter)
            <span class="mrtg-badge {{ $selectedRouter->status === 'online' ? '' : 'offline' }}">
                <span style="width:6px;height:6px;border-radius:50%;background:currentColor;"></span>
                {{ strtoupper($selectedRouter->status) }}
            </span>
            @endif
        </form>

        <div style="display: flex; align-items: center; gap: 12px;">
            <div class="refresh-indicator" id="refreshIndicator" style="{{ $selectedRouter ? '' : 'display:none' }}">
                <span class="refresh-dot"></span>
                <span id="refreshCountdown">Auto-refresh: 30s</span>
            </div>
            <form action="{{ route('traffic.record-stats') }}" method="POST" style="margin: 0;">
                @csrf
                <button type="submit" class="mrtg-btn" title="Rekam manual sekarang">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;">
                        <path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path><path d="M12 8v4l3 3"></path>
                    </svg>
                    Rekam Manual
                </button>
            </form>
            @if(auth()->user()->isSuperAdmin() || auth()->user()->isAdmin())
                @if($selectedRouter)
                <a href="{{ route('mrtg.settings', $selectedRouter->id) }}" class="mrtg-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;">
                        <path d="M12 15a3 3 0 100-6 3 3 0 000 6z"></path><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"></path>
                    </svg>
                    Settings
                </a>
                @endif
            @endif
            <a href="{{ route('traffic.index') }}" class="mrtg-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                </svg>
                Monitor Klasik
            </a>
        </div>
    </div>
</div>

@if(!$selectedRouter)
{{-- No router selected state --}}
<div class="card">
    <div class="no-router-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M5 12H3a9 9 0 0018 0h-2M12 3v2m0 14v2M4.22 4.22l1.42 1.42m12.72 12.72l1.42 1.42M4.22 19.78l1.42-1.42m12.72-12.72l1.42-1.42"/>
        </svg>
        <h3 style="color:#e8eaf6;font-size:20px;margin-bottom:8px;">Pilih Router</h3>
        <p style="color:#4a5568;font-size:14px;">Pilih router dari dropdown di atas untuk melihat grafik traffic semua interface.</p>
    </div>
</div>

@else
{{-- Interface Grid --}}
@if(empty($interfacesWithStats))
<div class="card">
    <div class="mrtg-empty">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <h3 style="color:#e8eaf6;font-size:18px;margin-bottom:8px;">Belum Ada Data Traffic</h3>
        <p style="color:#4a5568;font-size:13px;max-width:400px;margin:0 auto;">
            Data traffic akan muncul setelah cron job <code style="background:rgba(255,255,255,0.08);padding:2px 6px;border-radius:4px;color:#00d4ff;">mantik:record-stats</code> berjalan minimal sekali.
            @if($selectedRouter->status !== 'online')
            <br><br><span style="color:#ff5050;">⚠️ Router dalam status <strong>{{ $selectedRouter->status }}</strong>. Pastikan router online dan dapat diakses.</span>
            @endif
        </p>
    </div>
</div>
@else
<div class="mrtg-grid" id="mrtgGrid">
    @foreach($interfacesWithStats as $iface)
    @php
        $isActive = $iface['current_rx'] > 0 || $iface['current_tx'] > 0;
        $rxMbps = round($iface['current_rx'] / 1_000_000, 3);
        $txMbps = round($iface['current_tx'] / 1_000_000, 3);
        $avgRxMbps = round($iface['avg_rx'] / 1_000_000, 3);
        $avgTxMbps = round($iface['avg_tx'] / 1_000_000, 3);
        $maxRxMbps = round($iface['max_rx'] / 1_000_000, 3);
        $maxTxMbps = round($iface['max_tx'] / 1_000_000, 3);
        $detailUrl = route('traffic.mrtg-detail', ['router' => $selectedRouter->id, 'interface' => urlencode($iface['name'])]);
    @endphp
    <a href="{{ $detailUrl }}" class="mrtg-card" data-iface="{{ $iface['name'] }}" data-router="{{ $selectedRouter->id }}">
        <div class="mrtg-card-header">
            <div class="mrtg-iface-name">
                <span class="mrtg-iface-dot {{ $isActive ? '' : 'inactive' }}"></span>
                {{ $iface['display_name'] ?? $iface['name'] }}
            </div>
            <div class="mrtg-last-seen">
                @if($iface['last_seen'])
                    {{ $iface['last_seen']->diffForHumans() }}
                @else
                    Tidak ada data
                @endif
            </div>
        </div>
        <div class="mrtg-chart-area">
            <canvas id="miniChart_{{ Str::slug($iface['name']) }}" style="width:100%;height:100%;"></canvas>
        </div>
        <div class="mrtg-stats-row">
            <div class="mrtg-stat-cell">
                <div class="mrtg-stat-label">
                    <span class="legend-dot" style="background:#00e676;margin-right:4px;"></span>RX Saat Ini
                </div>
                <div class="mrtg-stat-value rx">{{ $rxMbps }} <span class="mrtg-stat-unit">Mbps</span></div>
            </div>
            <div class="mrtg-stat-cell">
                <div class="mrtg-stat-label">
                    <span class="legend-dot" style="background:#00d4ff;margin-right:4px;"></span>TX Saat Ini
                </div>
                <div class="mrtg-stat-value tx">{{ $txMbps }} <span class="mrtg-stat-unit">Mbps</span></div>
            </div>
            <div class="mrtg-stat-cell">
                <div class="mrtg-stat-label">
                    <span class="legend-dot" style="background:#ffd740;margin-right:4px;"></span>Peak RX (24j)
                </div>
                <div class="mrtg-stat-value peak">{{ $maxRxMbps }} <span class="mrtg-stat-unit">Mbps</span></div>
            </div>
        </div>
    </a>
    @endforeach
</div>
@endif
@endif

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const routerId = '{{ $selectedRouter?->id ?? '' }}';
const miniCharts = {};

// Initialize mini charts for each interface card
function initMiniCharts() {
    document.querySelectorAll('.mrtg-card[data-iface]').forEach(card => {
        const ifaceName = card.dataset.iface;
        const canvas = card.querySelector('canvas');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        const emptyData = Array(30).fill(0);

        miniCharts[ifaceName] = new Chart(ctx, {
            type: 'line',
            data: {
                labels: Array(30).fill(''),
                datasets: [
                    {
                        label: 'RX',
                        data: [...emptyData],
                        borderColor: '#00e676',
                        backgroundColor: 'rgba(0,230,118,0.12)',
                        borderWidth: 1.5,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 0,
                    },
                    {
                        label: 'TX',
                        data: [...emptyData],
                        borderColor: '#00d4ff',
                        backgroundColor: 'rgba(0,212,255,0.08)',
                        borderWidth: 1.5,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 0,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                interaction: { intersect: false },
                plugins: { legend: { display: false }, tooltip: { enabled: false } },
                scales: {
                    x: { display: false },
                    y: {
                        display: true,
                        min: 0,
                        grid: { color: 'rgba(255,255,255,0.03)', drawBorder: false },
                        ticks: { color: '#2d3748', font: { size: 9 }, maxTicksLimit: 3, callback: v => v.toFixed(1) },
                        border: { display: false }
                    }
                }
            }
        });
    });
}

// Fetch live traffic for all interfaces and update mini charts
// Uses parallel fetches (Promise.allSettled) instead of serial await to avoid
// blocking for 97+ interfaces (which was causing "Live Auto-update Active" to never show)
async function updateMiniCharts() {
    if (!routerId) return;

    const cards = document.querySelectorAll('.mrtg-card[data-iface]');
    const fetchPromises = Array.from(cards).map(async (card) => {
        const ifaceName = card.dataset.iface;
        const encodedIface = encodeURIComponent(ifaceName);
        const url = `{{ url('traffic') }}/${routerId}/${encodedIface}/mrtg-data?period=5m`;

        try {
            const res = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
            const json = await res.json();

            if (json.success && miniCharts[ifaceName]) {
                const chart = miniCharts[ifaceName];
                
                let labels = json.labels || [];
                let rxData = json.rx || [];
                let txData = json.tx || [];

                // Pad arrays to always have 30 points (shows progression over time visually)
                if (rxData.length < 30) {
                    const padLen = 30 - rxData.length;
                    labels = [...Array(padLen).fill(''), ...labels];
                    rxData = [...Array(padLen).fill(0), ...rxData];
                    txData = [...Array(padLen).fill(0), ...txData];
                } else if (rxData.length > 30) {
                    labels = labels.slice(-30);
                    rxData = rxData.slice(-30);
                    txData = txData.slice(-30);
                }

                chart.data.labels = labels;
                chart.data.datasets[0].data = rxData;
                chart.data.datasets[1].data = txData;
                chart.update('none');

                // Update current stats text in the card
                if (json.stats) {
                    const statCells = card.querySelectorAll('.mrtg-stat-value');
                    if (statCells[0]) statCells[0].innerHTML = json.stats.current_rx + ' <span class="mrtg-stat-unit">Mbps</span>';
                    if (statCells[1]) statCells[1].innerHTML = json.stats.current_tx + ' <span class="mrtg-stat-unit">Mbps</span>';
                    if (statCells[2]) statCells[2].innerHTML = json.stats.peak_rx + ' <span class="mrtg-stat-unit">Mbps</span>';
                }
            }
        } catch (e) {
            // silently ignore individual interface errors
        }
    });

    // Wait for all fetches to complete (or fail) in parallel
    await Promise.allSettled(fetchPromises);
}

// Countdown timer for auto-refresh
let lastStats = null;
let lastTimestamp = 0;

async function liveUpdateAll() {
    if (!routerId) return;
    const url = `{{ url('api/routers') }}/${routerId}/live-all-traffic`;

    try {
        const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const json = await res.json();
        
        if (json.success && json.data) {
            const nowTime = json.timestamp;
            const currentStats = json.data;
            
            if (lastStats && lastTimestamp > 0) {
                const timeDiff = nowTime - lastTimestamp;
                if (timeDiff > 0) {
                    const nowLabel = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                    
                    document.querySelectorAll('.mrtg-card[data-iface]').forEach(card => {
                        const ifaceName = card.dataset.iface;
                        if (currentStats[ifaceName] && lastStats[ifaceName] && miniCharts[ifaceName]) {
                            const curRx = currentStats[ifaceName]['rx-byte'];
                            const curTx = currentStats[ifaceName]['tx-byte'];
                            const lastRx = lastStats[ifaceName]['rx-byte'];
                            const lastTx = lastStats[ifaceName]['tx-byte'];
                            
                            const rxDiff = Math.max(0, curRx - lastRx);
                            const txDiff = Math.max(0, curTx - lastTx);
                            
                            const rxMbps = ((rxDiff * 8) / timeDiff) / 1_000_000;
                            const txMbps = ((txDiff * 8) / timeDiff) / 1_000_000;
                            
                            const chart = miniCharts[ifaceName];
                            
                            chart.data.labels.push(nowLabel);
                            chart.data.datasets[0].data.push(rxMbps);
                            chart.data.datasets[1].data.push(txMbps);
                            
                            // Keep maximum 60 points for the live overview (3 mins at 3s)
                            if (chart.data.labels.length > 60) {
                                chart.data.labels.shift();
                                chart.data.datasets[0].data.shift();
                                chart.data.datasets[1].data.shift();
                            }
                            chart.update('none');
                            
                            // Update text
                            const statCells = card.querySelectorAll('.mrtg-stat-value');
                            if (statCells[0]) statCells[0].innerHTML = rxMbps.toFixed(3) + ' <span class="mrtg-stat-unit">Mbps</span>';
                            if (statCells[1]) statCells[1].innerHTML = txMbps.toFixed(3) + ' <span class="mrtg-stat-unit">Mbps</span>';
                            
                            // Peak is tricky to update live easily without storing it, we leave peak as is from initial load
                            
                            // Update active dot
                            const dot = card.querySelector('.mrtg-iface-dot');
                            if (dot) {
                                if (rxMbps > 0 || txMbps > 0) {
                                    dot.classList.remove('inactive');
                                } else {
                                    dot.classList.add('inactive');
                                }
                            }
                        }
                    });
                }
            }
            lastStats = currentStats;
            lastTimestamp = nowTime;
        }
    } catch (e) {
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (routerId) {
        initMiniCharts();
        
        // Aktifkan label dan live update segera — jangan tunggu updateMiniCharts()
        // selesai karena router dengan 97+ interface bisa memakan waktu sangat lama
        const el = document.getElementById('refreshCountdown');
        if (el) el.textContent = 'Live Auto-update Active';
        
        // Start live update interval immediately
        setInterval(liveUpdateAll, 3000);
        
        // Load historical data from database in the background (parallel)
        updateMiniCharts();
    }
});
</script>
@endpush
