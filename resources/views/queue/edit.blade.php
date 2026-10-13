@extends('layouts.app')

@section('title', 'Edit Queue')
@section('page-title', 'Edit Queue')

@section('content')
<div class="page-header">
    <div class="page-header-title">
        <h2>Edit Queue: {{ $queue->name }}</h2>
        <p>Perbarui limitasi bandwidth untuk target ini</p>
    </div>
    <a href="{{ route('queue.index') }}" class="btn btn-outline">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Kembali
    </a>
</div>

<div class="grid" style="grid-template-columns: 2fr 1fr; gap: 24px;">
    <div class="card">
        <form action="{{ route('queue.update', $queue) }}" method="POST">
            @csrf @method('PUT')
            
            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label" for="name">Nama Queue *</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $queue->name) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="target">Target (IP / Subnet) *</label>
                    <input type="text" name="target" id="target" class="form-control font-mono" value="{{ old('target', $queue->target) }}" required>
                </div>
            </div>

            <div class="card-header" style="margin-top: 24px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 24px;">
                <div class="card-title">Max Limit (Bandwidth Maksimal)</div>
            </div>
            
            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label" for="max_limit_rx" style="color: #00e676;">Download (RX) *</label>
                    <select name="max_limit_rx" id="max_limit_rx" class="form-control font-mono" required>
                        @php $opts = ['512k','1M','2M','3M','5M','10M','20M','50M','100M']; @endphp
                        @foreach($opts as $opt)
                        <option value="{{ $opt }}" {{ $rx == $opt ? 'selected' : '' }}>{{ $opt === '512k' ? '512 kbps' : str_replace('M', ' Mbps', $opt) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="max_limit_tx" style="color: #00d4ff;">Upload (TX) *</label>
                    <select name="max_limit_tx" id="max_limit_tx" class="form-control font-mono" required>
                        @foreach($opts as $opt)
                        <option value="{{ $opt }}" {{ $tx == $opt ? 'selected' : '' }}>{{ $opt === '512k' ? '512 kbps' : str_replace('M', ' Mbps', $opt) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="comment">Komentar / Catatan</label>
                <input type="text" name="comment" id="comment" class="form-control" value="{{ old('comment', $queue->comment) }}">
            </div>

            <div style="margin-top: 24px;">
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v14a2 2 0 01-2 2z"/></svg>
                    Update & Sync ke MikroTik
                </button>
            </div>
        </form>
    </div>

    <div>
        <div class="card mb-24">
            <div class="card-title" style="margin-bottom: 16px;">Status Sinkronisasi</div>
            @if($queue->mikrotik_id)
            <div class="d-flex align-center gap-8 mb-12">
                <div style="width:10px;height:10px;border-radius:50%;background:#00e676;box-shadow:0 0 8px rgba(0,230,118,0.5);"></div>
                <span class="font-bold">Tersinkronisasi</span>
            </div>
            <div class="text-sm font-mono text-muted mb-4">ID: {{ $queue->mikrotik_id }}</div>
            <div class="text-sm font-mono text-muted">Router: {{ $queue->router->name }}</div>
            @else
            <div class="d-flex align-center gap-8 mb-12">
                <div style="width:10px;height:10px;border-radius:50%;background:#ff4444;"></div>
                <span class="font-bold" style="color: #ff4444;">Tidak Sinkron</span>
            </div>
            <p class="text-sm text-muted">Rule ini tidak tertaut dengan ID di MikroTik.</p>
            @endif
        </div>
        
        <div class="card">
            <div class="card-title" style="margin-bottom: 16px; color: #ff4444;">Danger Zone</div>
            <form action="{{ route('queue.destroy', $queue) }}" method="POST" onsubmit="return confirm('Hapus rule queue ini?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-outline w-100" style="color: #ff4444; border-color: rgba(255,68,68,0.3);">Hapus Queue</button>
            </form>
        </div>
    </div>
</div>
@endsection
