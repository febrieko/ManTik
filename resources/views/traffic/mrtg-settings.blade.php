@extends('layouts.app')

@section('title', 'MRTG Interface Settings')
@section('page-title', 'MRTG Interface Settings')
@section('breadcrumb')
    <a href="{{ route('traffic.mrtg') }}">MRTG Monitor</a>
    <span class="separator">/</span>
    <span class="current">Settings</span>
@endsection

@push('styles')
<style>
    .settings-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 16px;
    }
    .interface-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .interface-item {
        background: var(--card-bg, #111827);
        border: 1px solid rgba(255,255,255,0.07);
        border-radius: 12px;
        padding: 16px;
        display: flex;
        align-items: center;
        gap: 16px;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .interface-item:hover {
        border-color: rgba(255,255,255,0.15);
    }
    .interface-item.sortable-ghost {
        opacity: 0.4;
        background: rgba(255,255,255,0.02);
    }
    .drag-handle {
        cursor: grab;
        color: #4a5568;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 4px;
    }
    .drag-handle:active {
        cursor: grabbing;
    }
    .iface-info {
        flex: 1;
        display: grid;
        grid-template-columns: 200px 1fr;
        gap: 16px;
        align-items: center;
    }
    .iface-name {
        font-weight: 600;
        color: #e8eaf6;
        font-size: 14px;
    }
    .iface-inputs {
        display: flex;
        gap: 12px;
    }
    .iface-inputs input {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.1);
        color: #e8eaf6;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 13px;
        width: 100%;
        transition: border-color 0.2s;
    }
    .iface-inputs input:focus {
        border-color: #00d4ff;
        outline: none;
    }
    .iface-actions {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    /* Toggle Switch */
    .switch {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 24px;
    }
    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: rgba(255,255,255,0.1);
        transition: .4s;
        border-radius: 24px;
    }
    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }
    input:checked + .slider {
        background-color: #00e676;
    }
    input:checked + .slider:before {
        transform: translateX(20px);
    }
    .status-label {
        font-size: 12px;
        font-weight: 600;
        width: 40px;
        text-align: center;
    }
    .status-label.on { color: #00e676; }
    .status-label.off { color: #4a5568; }
    
    .stats-bar {
        background: rgba(255,255,255,0.02);
        border: 1px solid rgba(255,255,255,0.05);
        padding: 12px 20px;
        border-radius: 8px;
        display: flex;
        gap: 24px;
        margin-bottom: 20px;
    }
    .stat-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
    }
    .stat-val {
        font-weight: 700;
        color: #e8eaf6;
    }
</style>
@endpush

@section('content')
<div class="settings-header">
    <div style="display: flex; align-items: center; gap: 16px;">
        <form method="GET" action="" id="routerForm" style="display: flex; align-items: center; gap: 12px;">
            <label style="font-size:13px;color:#8892b0;font-weight:600;">Router:</label>
            <select class="form-control" onchange="window.location.href='{{ url('traffic/mrtg/settings') }}/' + this.value">
                @foreach($routers as $r)
                <option value="{{ $r->id }}" {{ $router->id == $r->id ? 'selected' : '' }}>
                    {{ $r->name }} ({{ $r->host }})
                </option>
                @endforeach
            </select>
        </form>
    </div>
    
    <div style="display: flex; gap: 12px;">
        <form action="{{ route('mrtg.settings.sync', $router->id) }}" method="POST" onsubmit="return confirm('Sync akan mengambil daftar interface terbaru dari router. Lanjutkan?')">
            @csrf
            <button type="submit" class="btn btn-secondary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;margin-right:6px;">
                    <path d="M21 2v6h-6"></path><path d="M3 12a9 9 0 0 1 15-6.7L21 8"></path><path d="M3 22v-6h6"></path><path d="M21 12a9 9 0 0 1-15 6.7L3 16"></path>
                </svg>
                Sync dari Router
            </button>
        </form>
        
        <button type="button" class="btn btn-primary" onclick="document.getElementById('settingsForm').submit()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;margin-right:6px;">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline>
            </svg>
            Simpan Perubahan
        </button>
    </div>
</div>

@php
    // Gabungkan interface dari DB (settings) dan dari traffic logs (jika belum ada di setting)
    $allInterfaces = collect();
    
    // 1. Masukkan yang ada di setting dulu (sesuai urutan)
    foreach($settings as $setting) {
        $allInterfaces->push([
            'id' => $setting->id,
            'name' => $setting->interface_name,
            'display_name' => $setting->display_name,
            'is_visible' => $setting->is_visible,
            'description' => $setting->description,
            'is_new' => false
        ]);
    }
    
    // 2. Tambahkan yang ada di logs tapi belum di setting
    foreach($knownInterfaces as $ifaceName) {
        if(!isset($settings[$ifaceName])) {
            $allInterfaces->push([
                'id' => null,
                'name' => $ifaceName,
                'display_name' => '',
                'is_visible' => false,
                'description' => '',
                'is_new' => true
            ]);
        }
    }
    
    $activeCount = $allInterfaces->where('is_visible', true)->count();
@endphp

<div class="stats-bar">
    <div class="stat-item">
        <span style="color:#8892b0;">Total Interface:</span>
        <span class="stat-val">{{ $allInterfaces->count() }}</span>
    </div>
    <div class="stat-item">
        <span style="color:#00e676;">Ditampilkan:</span>
        <span class="stat-val" id="activeCount">{{ $activeCount }}</span>
    </div>
    <div class="stat-item">
        <span style="color:#4a5568;">Disembunyikan:</span>
        <span class="stat-val" id="hiddenCount">{{ $allInterfaces->count() - $activeCount }}</span>
    </div>
</div>

<form action="{{ route('mrtg.settings.save', $router->id) }}" method="POST" id="settingsForm">
    @csrf
    
    <div class="interface-list" id="sortableList">
        @foreach($allInterfaces as $index => $iface)
        <div class="interface-item" data-id="{{ $iface['id'] }}">
            <div class="drag-handle" title="Drag untuk mengubah urutan">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px;">
                    <line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line>
                </svg>
            </div>
            
            <div class="iface-info">
                <div class="iface-name">
                    {{ $iface['name'] }}
                    @if($iface['is_new'])
                        <span style="font-size:10px; background:rgba(255,255,255,0.1); padding:2px 6px; border-radius:4px; margin-left:8px; color:#8892b0;">NEW</span>
                    @endif
                </div>
                <div class="iface-inputs">
                    <input type="hidden" name="interfaces[{{ $iface['name'] }}][sort_order]" class="sort-order-input" value="{{ $index }}">
                    <input type="text" name="interfaces[{{ $iface['name'] }}][display_name]" value="{{ $iface['display_name'] }}" placeholder="Label Tampilan Kustom (Opsional)">
                    <input type="text" name="interfaces[{{ $iface['name'] }}][description]" value="{{ $iface['description'] }}" placeholder="Deskripsi/Catatan (Opsional)">
                </div>
            </div>
            
            <div class="iface-actions">
                <label class="switch">
                    <input type="checkbox" name="interfaces[{{ $iface['name'] }}][visible]" value="1" class="visibility-toggle" {{ $iface['is_visible'] ? 'checked' : '' }} data-url="{{ $iface['id'] ? route('mrtg.settings.toggle', ['router' => $router->id, 'setting' => $iface['id']]) : '' }}">
                    <span class="slider"></span>
                </label>
                <div class="status-label {{ $iface['is_visible'] ? 'on' : 'off' }}">
                    {{ $iface['is_visible'] ? 'ON' : 'OFF' }}
                </div>
            </div>
        </div>
        @endforeach
    </div>
</form>
@endsection

@push('scripts')
<!-- Gunakan Sortable.js untuk drag-drop reordering -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi Sortable
    const list = document.getElementById('sortableList');
    if (list) {
        new Sortable(list, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: function() {
                // Update hidden inputs for sort_order
                document.querySelectorAll('.sort-order-input').forEach((input, index) => {
                    input.value = index;
                });
                
                // Opsional: Langsung simpan urutan ke backend via AJAX
                saveOrder();
            }
        });
    }

    // Toggle switch logic (AJAX jika data sudah ada di DB)
    document.querySelectorAll('.visibility-toggle').forEach(toggle => {
        toggle.addEventListener('change', async function() {
            const label = this.closest('.iface-actions').querySelector('.status-label');
            const isChecked = this.checked;
            
            // Update UI label
            if (isChecked) {
                label.textContent = 'ON';
                label.className = 'status-label on';
            } else {
                label.textContent = 'OFF';
                label.className = 'status-label off';
            }
            
            // Update counter
            updateCounters();

            // Jika setting sudah ada di DB, bisa langsung toggle via AJAX
            const url = this.dataset.url;
            if (url) {
                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    });
                    // Biarkan error handling senyap
                } catch (e) {
                    console.error("Gagal toggle", e);
                }
            }
        });
    });
    
    function updateCounters() {
        const total = document.querySelectorAll('.visibility-toggle').length;
        const active = document.querySelectorAll('.visibility-toggle:checked').length;
        
        const activeEl = document.getElementById('activeCount');
        const hiddenEl = document.getElementById('hiddenCount');
        
        if (activeEl) activeEl.textContent = active;
        if (hiddenEl) hiddenEl.textContent = (total - active);
    }
    
    async function saveOrder() {
        const orderData = [];
        document.querySelectorAll('.interface-item').forEach((item, index) => {
            const id = item.dataset.id;
            if (id) {
                orderData.push({ id: id, sort_order: index });
            }
        });
        
        if (orderData.length > 0) {
            try {
                await fetch('{{ route('mrtg.settings.reorder', $router->id) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ order: orderData })
                });
            } catch (e) {
                // Silently ignore
            }
        }
    }
});
</script>
@endpush
