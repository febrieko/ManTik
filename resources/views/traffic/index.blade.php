@extends('layouts.app')

@section('title', 'Traffic Monitor')
@section('page-title', 'Traffic Monitor')

@section('content')
<div class="page-header">
    <div class="page-header-title">
        <h2>Monitor Traffic</h2>
        <p>Pantau penggunaan bandwidth secara langsung (Live) atau riwayat log (History)</p>
    </div>
</div>

<div class="grid grid-3 mb-24" style="grid-template-columns: 1fr 1fr 1fr;">
    {{-- Seleksi Router, Interface & Periode --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">Filter Monitor</div>
        </div>
        <form action="{{ route('traffic.index') }}" method="GET" id="trafficForm">
            <div class="form-group">
                <label class="form-label" for="router_id">Router</label>
                <select name="router_id" id="router_id" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Pilih Router --</option>
                    @foreach($routers as $r)
                    <option value="{{ $r->id }}" {{ $selectedRouterId == $r->id ? 'selected' : '' }}>
                        {{ $r->name }} ({{ $r->status }})
                    </option>
                    @endforeach
                </select>
            </div>
            
            @if($selectedRouter && $selectedRouter->status === 'online')
            <div class="form-group mt-16">
                <label class="form-label" for="interface">Interface</label>
                <select name="interface" id="interface" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Pilih Interface --</option>
                    @foreach($interfaces as $iface)
                    <option value="{{ $iface['name'] }}" {{ $selectedInterface == $iface['name'] ? 'selected' : '' }}>
                        {{ $iface['name'] }} {{ $iface['running'] ? '🟢' : '⚫' }}
                    </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="form-group mt-16">
                <label class="form-label" for="period">Periode Data</label>
                <select name="period" id="period" class="form-control" onchange="this.form.submit()">
                    <option value="live" {{ $period == 'live' ? 'selected' : '' }}>🔴 Live (Real-Time)</option>
                    <option value="1h" {{ $period == '1h' ? 'selected' : '' }}>🕒 1 Jam Terakhir</option>
                    <option value="1d" {{ $period == '1d' ? 'selected' : '' }}>📅 1 Hari Terakhir</option>
                    <option value="1w" {{ $period == '1w' ? 'selected' : '' }}>📆 1 Minggu Terakhir</option>
                    <option value="1m" {{ $period == '1m' ? 'selected' : '' }}>🗓 1 Bulan Terakhir</option>
                    <option value="1y" {{ $period == '1y' ? 'selected' : '' }}>🗓 1 Tahun Terakhir</option>
                </select>
            </div>
        </form>
    </div>

    {{-- Stats Box (Hanya aktif di mode Live) --}}
    <div class="card" style="grid-column: span 2;">
        <div class="card-header">
            <div class="card-title">
                Status Saat Ini: 
                <span id="monitorStatus" style="color: #4a5568;">
                    @if($period == 'live' && $selectedInterface)
                        Memulai Live Monitor...
                    @elseif($period != 'live')
                        Menampilkan Riwayat (History)
                    @else
                        Menunggu koneksi...
                    @endif
                </span>
            </div>
        </div>
        
        <div class="grid grid-2" style="height: 100%;">
            <div style="display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 20px; border-right: 1px solid rgba(255,255,255,0.05);">
                <div style="font-size: 12px; color: #8892b0; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Download (RX)</div>
                <div id="rxValue" style="font-size: 32px; font-weight: 800; color: #00e676; font-family: monospace;">
                    {{ $period != 'live' ? '-' : '0.00' }}
                </div>
                <div style="font-size: 14px; color: #4a5568;">Mbps</div>
            </div>
            <div style="display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 20px;">
                <div style="font-size: 12px; color: #8892b0; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Upload (TX)</div>
                <div id="txValue" style="font-size: 32px; font-weight: 800; color: #00d4ff; font-family: monospace;">
                    {{ $period != 'live' ? '-' : '0.00' }}
                </div>
                <div style="font-size: 14px; color: #4a5568;">Mbps</div>
            </div>
        </div>
    </div>
</div>

{{-- Grafik Traffic --}}
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            @if($period == 'live')
                Live Graph (5 Menit Terakhir)
            @else
                Grafik Riwayat ({{ strtoupper($period) }})
            @endif
        </div>
        <div style="display: flex; gap: 12px; font-size: 12px;">
            <span style="color: #00e676; display: flex; align-items: center; gap: 4px;">
                <span style="width: 8px; height: 2px; background: #00e676; display: inline-block; border-radius: 2px;"></span> RX
            </span>
            <span style="color: #00d4ff; display: flex; align-items: center; gap: 4px;">
                <span style="width: 8px; height: 2px; background: #00d4ff; display: inline-block; border-radius: 2px;"></span> TX
            </span>
        </div>
    </div>
    
    <div class="chart-container" style="height: 400px; position: relative;">
        <canvas id="trafficChart"></canvas>
        @if(!$selectedInterface)
        <div id="chartOverlay" style="position: absolute; inset: 0; background: rgba(13,20,36,0.8); display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 10;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 48px; height: 48px; color: #4a5568; margin-bottom: 16px;"><path d="M21 12V7H5a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v5a2 2 0 01-2 2zM3 17v5a2 2 0 002 2h14a2 2 0 002-2v-5a2 2 0 00-2-2H5a2 2 0 00-2 2z"/></svg>
            <h3 style="color: #e8eaf6; font-size: 18px; margin-bottom: 8px;">Tidak Ada Data</h3>
            <p style="color: #8892b0; font-size: 13px;">Pilih router dan interface untuk mulai memonitor traffic.</p>
        </div>
        @endif
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endsection

@push('scripts')
<script>
let trafficChart = null;
const period = '{{ $period }}';
const routerId = '{{ $selectedRouterId }}';
const ifaceName = '{{ $selectedInterface }}';

function initLiveChart() {
    const ctx = document.getElementById('trafficChart').getContext('2d');
    const MAX_DATA_POINTS = 150; // 5 menit dengan interval 2 detik
    const emptyData = Array(MAX_DATA_POINTS).fill(0);
    const emptyLabels = Array(MAX_DATA_POINTS).fill('');

    trafficChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: emptyLabels,
            datasets: [
                { label: 'Download (RX)', data: emptyData.slice(), borderColor: '#00e676', backgroundColor: 'rgba(0, 230, 118, 0.08)', borderWidth: 2, fill: true, tension: 0.4, pointRadius: 0 },
                { label: 'Upload (TX)', data: emptyData.slice(), borderColor: '#00d4ff', backgroundColor: 'rgba(0, 212, 255, 0.08)', borderWidth: 2, fill: true, tension: 0.4, pointRadius: 0 }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false, animation: { duration: 500, easing: 'linear' }, interaction: { intersect: false, mode: 'index' },
            plugins: { legend: { display: false }, tooltip: { backgroundColor: 'rgba(13, 20, 36, 0.9)', borderColor: 'rgba(255,255,255,0.1)', borderWidth: 1, titleColor: '#8892b0', bodyColor: '#e8eaf6', callbacks: { label: ctx => `${ctx.dataset.label}: ${ctx.parsed.y} Mbps` } } },
            scales: { x: { grid: { color: 'rgba(255,255,255,0.02)' }, ticks: { display: false }, border: { display: false } }, y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#4a5568', font: { size: 11 }, callback: v => v + ' M' }, border: { display: false }, min: 0 } }
        }
    });

    const statusText = document.getElementById('monitorStatus');
    statusText.innerHTML = `<span style="color: #00e676;">🟢 Memonitor <span style="font-weight:bold; color: #fff;">${ifaceName}</span></span>`;
    
    // Fix for subfolder hosting: use Laravel url() helper
    const baseUrl = '{{ url("api/routers") }}';
    const url = `${baseUrl}/${routerId}/traffic/${encodeURIComponent(ifaceName)}`;

    let lastRxByte = null;
    let lastTxByte = null;
    let lastTime = null;

    setInterval(() => {
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
        .then(res => res.json())
        .then(json => {
            if (json.success && json.data) {
                // Now receiving BPS directly from API
                const rxBps = json.data['rx-bps'] || 0;
                const txBps = json.data['tx-bps'] || 0;
                
                const rxMbps = (rxBps / 1000000).toFixed(2);
                const txMbps = (txBps / 1000000).toFixed(2);

                document.getElementById('rxValue').textContent = rxMbps;
                document.getElementById('txValue').textContent = txMbps;

                const timeLabel = new Date().toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit', second:'2-digit'});
                trafficChart.data.labels.push(timeLabel);
                trafficChart.data.datasets[0].data.push(rxMbps);
                trafficChart.data.datasets[1].data.push(txMbps);
                
                if (trafficChart.data.labels.length > MAX_DATA_POINTS) {
                    trafficChart.data.labels.shift();
                    trafficChart.data.datasets[0].data.shift();
                    trafficChart.data.datasets[1].data.shift();
                }
                trafficChart.update('none');
            }
        });
    }, 2000);
}

function initHistoryChart(historyData) {
    const ctx = document.getElementById('trafficChart').getContext('2d');
    
    trafficChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: historyData.labels,
            datasets: [
                { label: 'Download (RX)', data: historyData.rx, borderColor: '#00e676', backgroundColor: 'rgba(0, 230, 118, 0.08)', borderWidth: 2, fill: true, tension: 0.4, pointRadius: 0, pointHoverRadius: 4 },
                { label: 'Upload (TX)', data: historyData.tx, borderColor: '#00d4ff', backgroundColor: 'rgba(0, 212, 255, 0.08)', borderWidth: 2, fill: true, tension: 0.4, pointRadius: 0, pointHoverRadius: 4 }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false, interaction: { intersect: false, mode: 'index' },
            plugins: { legend: { display: false }, tooltip: { backgroundColor: 'rgba(13, 20, 36, 0.9)', borderColor: 'rgba(255,255,255,0.1)', borderWidth: 1, titleColor: '#8892b0', bodyColor: '#e8eaf6', callbacks: { label: ctx => `${ctx.dataset.label}: ${ctx.parsed.y} Mbps` } } },
            scales: { x: { grid: { color: 'rgba(255,255,255,0.02)' }, ticks: { color: '#4a5568', font: { size: 10 }, maxTicksLimit: 12 }, border: { display: false } }, y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#4a5568', font: { size: 11 }, callback: v => v + ' M' }, border: { display: false }, min: 0 } }
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    if (ifaceName) {
        if (period === 'live') {
            initLiveChart();
        } else {
            const historicalData = @json($historicalData);
            if (historicalData && historicalData.labels.length > 0) {
                initHistoryChart(historicalData);
            } else {
                document.getElementById('chartOverlay').style.display = 'flex';
                document.querySelector('#chartOverlay h3').textContent = 'Belum Ada Data Historis';
                document.querySelector('#chartOverlay p').textContent = 'Data akan mulai terkumpul saat background process berjalan.';
            }
        }
    }
});
</script>
@endpush
