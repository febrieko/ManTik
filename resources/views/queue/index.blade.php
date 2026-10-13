@extends('layouts.app')

@section('title', 'Manajemen Queue')
@section('page-title', 'Queue')

@section('content')
<div class="page-header">
    <div class="page-header-title">
        <h2>Manajemen Bandwidth (Queue)</h2>
        <p>Kelola Simple Queue untuk membatasi kecepatan internet klien</p>
    </div>
    <div class="d-flex gap-12">
        @if($routers->count() > 0)
        <div class="dropdown">
            <button class="btn btn-outline dropdown-toggle" type="button" id="syncDropdown" data-bs-toggle="dropdown">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 2v6h-6"/><path d="M3 12a9 9 0 0115-6.7L21 8"/><path d="M3 22v-6h6"/><path d="M21 12a9 9 0 01-15 6.7L3 16"/></svg>
                Sync dari Router
            </button>
            <ul class="dropdown-menu" style="background: var(--bg-card); border: 1px solid var(--border-color); padding: 8px; border-radius: 8px; margin-top: 8px;">
                @foreach($routers as $router)
                <li>
                    <form action="{{ route('queue.sync-router', $router) }}" method="POST">
                        @csrf
                        <button class="btn btn-outline btn-sm w-100" style="text-align: left; margin-bottom: 4px; border:none;">{{ $router->name }}</button>
                    </form>
                </li>
                @endforeach
            </ul>
        </div>
        @endif
        <a href="{{ route('queue.create') }}" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
            Tambah Queue
        </a>
    </div>
</div>

<div class="card mb-24">
    <form action="{{ route('queue.index') }}" method="GET" class="d-flex gap-12 align-center">
        <div style="flex: 1; max-width: 300px;">
            <select name="router_id" class="form-control" onchange="this.form.submit()">
                <option value="">Semua Router</option>
                @foreach($routers as $r)
                <option value="{{ $r->id }}" {{ $routerId == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                @endforeach
            </select>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Nama Target</th>
                    <th>Target (IP/Subnet)</th>
                    <th>Max Limit (RX/TX)</th>
                    <th>Router</th>
                    <th>Status</th>
                    <th width="150">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($queues as $q)
                <tr>
                    <td class="font-bold">{{ $q->name }}</td>
                    <td class="font-mono text-sm" style="color: #00d4ff;">{{ $q->target }}</td>
                    <td>
                        @php
                            $limits = explode('/', $q->max_limit);
                            $rx = $limits[0] ?? '0';
                            $tx = $limits[1] ?? '0';
                        @endphp
                        <div class="d-flex gap-8 align-center text-sm font-mono">
                            <span style="color: #00e676;" title="Download (RX)"><svg viewBox="0 0 24 24" width="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg> {{ $rx }}</span>
                            <span>/</span>
                            <span style="color: #00d4ff;" title="Upload (TX)"><svg viewBox="0 0 24 24" width="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M17 8l-5-5-5 5M12 3v12"/></svg> {{ $tx }}</span>
                        </div>
                    </td>
                    <td>{{ $q->router->name ?? '-' }}</td>
                    <td>
                        <span class="badge {{ $q->is_disabled ? 'badge-danger' : 'badge-success' }}">
                            {{ $q->is_disabled ? 'Disabled' : 'Active' }}
                        </span>
                        @if(!$q->mikrotik_id)
                        <span class="badge badge-warning">⚠ Unsynced</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-8">
                            <form action="{{ route('queue.toggle', $q) }}" method="POST" style="margin: 0;">
                                @csrf
                                <button type="submit" class="btn {{ $q->is_disabled ? 'btn-success' : 'btn-warning' }} btn-sm">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        @if($q->is_disabled)
                                        <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                                        @else
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="9" x2="15" y2="15"/><line x1="15" y1="9" x2="9" y2="15"/>
                                        @endif
                                    </svg>
                                </button>
                            </form>
                            <a href="{{ route('queue.edit', $q) }}" class="btn btn-outline btn-sm">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </a>
                            <form action="{{ route('queue.destroy', $q) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Hapus rule queue {{ $q->name }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 40px;">
                        <div class="empty-state">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M13 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>
                            <h3>Belum ada rule Queue</h3>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($queues->hasPages())
    <div style="margin-top: 20px;">
        {{ $queues->links('pagination::bootstrap-4') }}
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('click', function(e) {
    if (e.target.matches('.dropdown-toggle') || e.target.closest('.dropdown-toggle')) {
        const menu = e.target.closest('.dropdown').querySelector('.dropdown-menu');
        menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
    } else {
        document.querySelectorAll('.dropdown-menu').forEach(m => m.style.display = 'none');
    }
});
</script>
<style>
.dropdown-menu { display: none; position: absolute; z-index: 100; min-width: 200px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
.dropdown { position: relative; }
</style>
@endpush
