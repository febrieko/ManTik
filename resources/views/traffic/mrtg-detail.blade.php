@extends('layouts.app')

@section('title', 'Detail Traffic - ' . $interface)
@section('page-title', 'MRTG Detail')

@push('styles')
<style>
    .mrtg-detail-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 24px;
    }
    .mrtg-detail-title {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .mrtg-detail-title h2 {
        font-size: 22px;
        font-weight: 800;
        color: #e8eaf6;
        margin: 0;
    }
    .iface-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 700;
        background: rgba(0, 212, 255, 0.12);
        color: #00d4ff;
        border: 1px solid rgba(0, 212, 255, 0.25);
        font-family: 'Courier New', monospace;
    }
    .mrtg-panels {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
    @media (max-width: 900px) {
        .mrtg-panels { grid-template-columns: 1fr; }
    }
    .mrtg-panel {
        background: var(--card-bg, #111827);
        border: 1px solid rgba(255,255,255,0.07);
        border-radius: 16px;
        overflow: hidden;
    }
    .mrtg-panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 20px;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        background: rgba(255,255,255,0.02);
    }
    .mrtg-panel-title {
        font-size: 13px;
        font-weight: 700;
        color: #e8eaf6;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .mrtg-panel-period {
        font-size: 11px;
        color: #4a5568;
        font-weight: 500;
        background: rgba(255,255,255,0.04);
        padding: 3px 8px;
        border-radius: 4px;
    }
    .mrtg-chart-wrapper {
        padding: 16px;
        height: 200px;
        position: relative;
    }
    .mrtg-stats-table {
        border-top: 1px solid rgba(255,255,255,0.05);
        display: grid;
        grid-template-columns: repeat(3, 1fr);
    }
    .mrtg-stat-item {
        padding: 12px 16px;
        border-right: 1px solid rgba(255,255,255,0.04);
        text-align: center;
    }
    .mrtg-stat-item:last-child { border-right: none; }
    .mrtg-stat-item .label {
        font-size: 10px;
        color: #4a5568;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
    }
    .mrtg-stat-item .value {
        font-size: 16px;
        font-weight: 800;
        font-family: 'Courier New', monospace;
    }
    .mrtg-stat-item .unit {
        font-size: 10px;
        font-weight: 400;
        color: #4a5568;
    }
    .color-rx { color: #00e676; }
    .color-tx { color: #00d4ff; }
    .color-peak { color: #ffd740; }
    .legend-strip {
        display: flex;
        align-items: center;
        gap: 16px;
        font-size: 11px;
        color: #8892b0;
    }
    .legend-line {
        width: 24px;
        height: 2px;
        border-radius: 2px;
        display: inline-block;
        vertical-align: middle;
        margin-right: 4px;
    }
    .loading-overlay {
        position: absolute;
        inset: 0;
        background: rgba(13,20,36,0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        z-index: 10;
    }
    .spinner {
        width: 28px;
        height: 28px;
        border: 3px solid rgba(0,212,255,0.15);
        border-top-color: #00d4ff;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
    .summary-card {
        background: var(--card-bg, #111827);
        border: 1px solid rgba(255,255,255,0.07);
        border-radius: 16px;
        padding: 20px 24px;
        margin-bottom: 20px;
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 16px;
    }
    @media (max-width: 900px) {
        .summary-card { grid-template-columns: repeat(3, 1fr); }
    }
    .summary-item { text-align: center; }
    .summary-label {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: #4a5568;
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
    }
    .summary-value {
        font-size: 20px;
        font-weight: 800;
        font-family: 'Courier New', monospace;
    }
    .summary-unit { font-size: 11px; font-weight: 400; color: #4a5568; }
    .back-btn {
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
    }
    .back-btn:hover {
        background: rgba(255,255,255,0.08);
        color: #e8eaf6;
    }
    .live-indicator {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: #00e676;
        font-weight: 600;
    }
    .live-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #00e676;
        animation: pulse-dot 1s infinite;
    }
    @keyframes pulse-dot {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.5; transform: scale(0.85); }
    }
</style>
@endpush

@section('content')
{{-- Back navigation --}}
<div style="margin-bottom: 20px;">
    <a href="{{ route('traffic.mrtg', ['router_id' => $router->id]) }}" class="back-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;">
            <polyline points="15 18 9 12 15 6"/>
        </svg>
        Kembali ke Overview
    </a>
</div>

{{-- Header --}}
<div class="mrtg-detail-header">
    <div class="mrtg-detail-title">
        <div>
            <h2>{{ $router->name }}</h2>
            <div style="margin-top:6px; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <span class="iface-badge">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;">
                        <path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>
                    </svg>
                    {{ $interface }}
                </span>
                <span class="live-indicator">
                    <span class="live-dot"></span>
                    Live Monitor
                </span>
            </div>
        </div>
    </div>
    <div style="display:flex; align-items:center; gap:12px; flex-wrap: wrap;">
        <div class="legend-strip">
            <span><span class="legend-line" style="background:#00e676;"></span>Download (RX)</span>
            <span><span class="legend-line" style="background:#00d4ff;"></span>Upload (TX)</span>
            <span><span class="legend-line" style="background:#ffd740;width:24px;border-top:2px dashed #ffd740;"></span>Peak</span>
        </div>
    </div>
</div>

{{-- Summary Stats Row --}}
<div class="summary-card" id="summaryCard">
    <div class="summary-item">
        <div class="summary-label"><span style="width:8px;height:8px;border-radius:50%;background:#00e676;display:inline-block;"></span>RX Saat Ini</div>
        <div class="summary-value color-rx" id="sum_current_rx">—<span class="summary-unit"> Mbps</span></div>
    </div>
    <div class="summary-item">
        <div class="summary-label"><span style="width:8px;height:8px;border-radius:50%;background:#00d4ff;display:inline-block;"></span>TX Saat Ini</div>
        <div class="summary-value color-tx" id="sum_current_tx">—<span class="summary-unit"> Mbps</span></div>
    </div>
    <div class="summary-item">
        <div class="summary-label">Avg RX</div>
        <div class="summary-value color-rx" id="sum_avg_rx">—<span class="summary-unit"> Mbps</span></div>
    </div>
    <div class="summary-item">
        <div class="summary-label">Avg TX</div>
        <div class="summary-value color-tx" id="sum_avg_tx">—<span class="summary-unit"> Mbps</span></div>
    </div>
    <div class="summary-item">
        <div class="summary-label"><span style="width:8px;height:8px;border-radius:50%;background:#ffd740;display:inline-block;"></span>Peak RX</div>
        <div class="summary-value color-peak" id="sum_peak_rx">—<span class="summary-unit"> Mbps</span></div>
    </div>
    <div class="summary-item">
        <div class="summary-label"><span style="width:8px;height:8px;border-radius:50%;background:#ffd740;display:inline-block;"></span>Peak TX</div>
        <div class="summary-value color-peak" id="sum_peak_tx">—<span class="summary-unit"> Mbps</span></div>
    </div>
</div>

{{-- 4 MRTG Panels --}}
<div class="mrtg-panels">
    {{-- Panel 1: Last 5 Minutes (Live) --}}
    <div class="mrtg-panel" id="panel-5m">
        <div class="mrtg-panel-header">
            <div class="mrtg-panel-title">
                <span class="live-dot"></span>
                5 Menit Terakhir
            </div>
            <span class="mrtg-panel-period">Interval: real-time</span>
        </div>
        <div class="mrtg-chart-wrapper">
            <div class="loading-overlay" id="loading-5m"><div class="spinner"></div></div>
            <canvas id="chart-5m"></canvas>
        </div>
        <div class="mrtg-stats-table" id="stats-5m">
            <div class="mrtg-stat-item">
                <div class="label"><span style="width:6px;height:6px;background:#00e676;border-radius:50%;display:inline-block;"></span>Avg RX</div>
                <div class="value color-rx" id="p5m_avg_rx">—<span class="unit"> Mbps</span></div>
            </div>
            <div class="mrtg-stat-item">
                <div class="label"><span style="width:6px;height:6px;background:#00d4ff;border-radius:50%;display:inline-block;"></span>Avg TX</div>
                <div class="value color-tx" id="p5m_avg_tx">—<span class="unit"> Mbps</span></div>
            </div>
            <div class="mrtg-stat-item">
                <div class="label"><span style="width:6px;height:6px;background:#ffd740;border-radius:50%;display:inline-block;"></span>Peak RX</div>
                <div class="value color-peak" id="p5m_peak_rx">—<span class="unit"> Mbps</span></div>
            </div>
        </div>
    </div>

    {{-- Panel 2: Last 1 Hour --}}
    <div class="mrtg-panel" id="panel-1h">
        <div class="mrtg-panel-header">
            <div class="mrtg-panel-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;color:#8892b0;">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                </svg>
                1 Jam Terakhir
            </div>
            <span class="mrtg-panel-period">Interval: per menit</span>
        </div>
        <div class="mrtg-chart-wrapper">
            <div class="loading-overlay" id="loading-1h"><div class="spinner"></div></div>
            <canvas id="chart-1h"></canvas>
        </div>
        <div class="mrtg-stats-table">
            <div class="mrtg-stat-item">
                <div class="label">Avg RX</div>
                <div class="value color-rx" id="p1h_avg_rx">—<span class="unit"> Mbps</span></div>
            </div>
            <div class="mrtg-stat-item">
                <div class="label">Avg TX</div>
                <div class="value color-tx" id="p1h_avg_tx">—<span class="unit"> Mbps</span></div>
            </div>
            <div class="mrtg-stat-item">
                <div class="label">Peak RX</div>
                <div class="value color-peak" id="p1h_peak_rx">—<span class="unit"> Mbps</span></div>
            </div>
        </div>
    </div>

    {{-- Panel 3: Last 24 Hours --}}
    <div class="mrtg-panel" id="panel-1d">
        <div class="mrtg-panel-header">
            <div class="mrtg-panel-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;color:#8892b0;">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                24 Jam Terakhir
            </div>
            <span class="mrtg-panel-period">Interval: per jam</span>
        </div>
        <div class="mrtg-chart-wrapper">
            <div class="loading-overlay" id="loading-1d"><div class="spinner"></div></div>
            <canvas id="chart-1d"></canvas>
        </div>
        <div class="mrtg-stats-table">
            <div class="mrtg-stat-item">
                <div class="label">Avg RX</div>
                <div class="value color-rx" id="p1d_avg_rx">—<span class="unit"> Mbps</span></div>
            </div>
            <div class="mrtg-stat-item">
                <div class="label">Avg TX</div>
                <div class="value color-tx" id="p1d_avg_tx">—<span class="unit"> Mbps</span></div>
            </div>
            <div class="mrtg-stat-item">
                <div class="label">Peak RX</div>
                <div class="value color-peak" id="p1d_peak_rx">—<span class="unit"> Mbps</span></div>
            </div>
        </div>
    </div>

    {{-- Panel 4: Last 7 Days --}}
    <div class="mrtg-panel" id="panel-1w">
        <div class="mrtg-panel-header">
            <div class="mrtg-panel-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;color:#8892b0;">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                </svg>
                7 Hari Terakhir
            </div>
            <span class="mrtg-panel-period">Interval: per hari</span>
        </div>
        <div class="mrtg-chart-wrapper">
            <div class="loading-overlay" id="loading-1w"><div class="spinner"></div></div>
            <canvas id="chart-1w"></canvas>
        </div>
        <div class="mrtg-stats-table">
            <div class="mrtg-stat-item">
                <div class="label">Avg RX</div>
                <div class="value color-rx" id="p1w_avg_rx">—<span class="unit"> Mbps</span></div>
            </div>
            <div class="mrtg-stat-item">
                <div class="label">Avg TX</div>
                <div class="value color-tx" id="p1w_avg_tx">—<span class="unit"> Mbps</span></div>
            </div>
            <div class="mrtg-stat-item">
                <div class="label">Peak RX</div>
                <div class="value color-peak" id="p1w_peak_rx">—<span class="unit"> Mbps</span></div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const ROUTER_ID = '{{ $router->id }}';
const INTERFACE = '{{ addslashes($interface) }}';
const BASE_URL = '{{ url("traffic") }}';

const charts = {};

const CHART_OPTIONS = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: { intersect: false, mode: 'index' },
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: 'rgba(10,16,30,0.95)',
            borderColor: 'rgba(255,255,255,0.1)',
            borderWidth: 1,
            titleColor: '#8892b0',
            bodyColor: '#e8eaf6',
            callbacks: {
                label: ctx => `${ctx.dataset.label}: ${ctx.parsed.y} Mbps`
            }
        }
    },
    scales: {
        x: {
            grid: { color: 'rgba(255,255,255,0.03)', drawBorder: false },
            ticks: { color: '#3a4458', font: { size: 10 }, maxTicksLimit: 8 },
            border: { display: false }
        },
        y: {
            grid: { color: 'rgba(255,255,255,0.04)', drawBorder: false },
            ticks: { color: '#3a4458', font: { size: 10 }, callback: v => v.toFixed(1) + ' M' },
            border: { display: false },
            min: 0,
        }
    }
};

function makeDatasets(rxData, txData, maxRx, maxTx) {
    return [
        {
            label: 'Download (RX)',
            data: rxData,
            borderColor: '#00e676',
            backgroundColor: 'rgba(0,230,118,0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4,
            pointRadius: 0,
            pointHoverRadius: 4,
        },
        {
            label: 'Upload (TX)',
            data: txData,
            borderColor: '#00d4ff',
            backgroundColor: 'rgba(0,212,255,0.08)',
            borderWidth: 2,
            fill: true,
            tension: 0.4,
            pointRadius: 0,
            pointHoverRadius: 4,
        },
        {
            label: 'Peak RX',
            data: maxRx,
            borderColor: 'rgba(255,215,64,0.5)',
            borderWidth: 1,
            borderDash: [4, 3],
            fill: false,
            tension: 0.3,
            pointRadius: 0,
        },
    ];
}

function initChart(canvasId, labels, rxData, txData, maxRx, maxTx) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    if (charts[canvasId]) charts[canvasId].destroy();

    charts[canvasId] = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: makeDatasets(rxData, txData, maxRx, maxTx),
        },
        options: CHART_OPTIONS,
    });
}

function updateStats(prefix, stats) {
    if (!stats) return;
    const set = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.innerHTML = val + '<span class="unit"> Mbps</span>';
    };
    set(`${prefix}_avg_rx`, stats.avg_rx);
    set(`${prefix}_avg_tx`, stats.avg_tx);
    set(`${prefix}_peak_rx`, stats.peak_rx);
}

function updateSummaryCard(stats) {
    if (!stats) return;
    const set = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.innerHTML = val + '<span class="summary-unit"> Mbps</span>';
    };
    set('sum_current_rx', stats.current_rx);
    set('sum_current_tx', stats.current_tx);
    set('sum_avg_rx', stats.avg_rx);
    set('sum_avg_tx', stats.avg_tx);
    set('sum_peak_rx', stats.peak_rx);
    set('sum_peak_tx', stats.peak_tx);
}

async function loadPanel(period) {
    const loadingEl = document.getElementById(`loading-${period}`);
    if (loadingEl) loadingEl.style.display = 'flex';

    const encodedIface = encodeURIComponent(INTERFACE);
    const url = `${BASE_URL}/${ROUTER_ID}/${encodedIface}/mrtg-data?period=${period}`;

    try {
        const res = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        });
        const json = await res.json();

        if (json.success) {
            initChart(
                `chart-${period}`,
                json.labels.length ? json.labels : ['Tidak ada data'],
                json.rx,
                json.tx,
                json.max_rx,
                json.max_tx,
            );

            const pfx = period.replace('-', '_');
            updateStats(`p${pfx}`, json.stats);

            // Update summary from 5m (most current)
            if (period === '5m') updateSummaryCard(json.stats);
        }
    } catch (e) {
        console.warn(`Failed to load panel ${period}:`, e);
    } finally {
        if (loadingEl) loadingEl.style.display = 'none';
    }
}

// Live update for 5m panel every 5 seconds
async function liveUpdate() {
    const loadingEl = document.getElementById('loading-5m');
    const encodedIface = encodeURIComponent(INTERFACE);

    // First fetch live bps from API
    const apiUrl = `{{ url('api/routers') }}/${ROUTER_ID}/traffic/${encodedIface}`;
    try {
        const res = await fetch(apiUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        });
        const json = await res.json();

        if (json.success && json.data && charts['chart-5m']) {
            const chart = charts['chart-5m'];
            const now = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            const rxMbps = (json.data['rx-bps'] / 1_000_000).toFixed(3);
            const txMbps = (json.data['tx-bps'] / 1_000_000).toFixed(3);

            // Keep last 150 data points (5 minutes at 2s interval)
            const MAX = 150;
            chart.data.labels.push(now);
            chart.data.datasets[0].data.push(parseFloat(rxMbps));
            chart.data.datasets[1].data.push(parseFloat(txMbps));
            chart.data.datasets[2].data.push(parseFloat(rxMbps)); // peak line = current for live

            if (chart.data.labels.length > MAX) {
                chart.data.labels.shift();
                chart.data.datasets.forEach(ds => ds.data.shift());
            }
            chart.update('none');

            // Update summary
            document.getElementById('sum_current_rx').innerHTML = rxMbps + '<span class="summary-unit"> Mbps</span>';
            document.getElementById('sum_current_tx').innerHTML = txMbps + '<span class="summary-unit"> Mbps</span>';
        }
    } catch (e) {
        // ignore
    }
}

document.addEventListener('DOMContentLoaded', async () => {
    // Load all 4 panels
    await loadPanel('5m');
    await loadPanel('1h');
    await loadPanel('1d');
    await loadPanel('1w');

    // Live update for 5m panel every 3 seconds
    setInterval(liveUpdate, 3000);

    // Refresh 1h panel every 1 minute
    setInterval(() => loadPanel('1h'), 60_000);

    // Refresh 1d panel every 5 minutes
    setInterval(() => loadPanel('1d'), 300_000);

    // Refresh 1w panel every 15 minutes
    setInterval(() => loadPanel('1w'), 900_000);
});
</script>
@endpush
