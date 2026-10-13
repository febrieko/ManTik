@extends('layouts.app')

@section('title', 'Manajemen PPPoE')
@section('page-title', 'User PPPoE')

@section('content')
<div class="page-header">
    <div class="page-header-title">
        <h2>Manajemen User PPPoE</h2>
        <p>Kelola data pelanggan dan autentikasi PPPoE</p>
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
                    <form action="{{ route('pppoe.sync-router', $router) }}" method="POST">
                        @csrf
                        <button class="btn btn-outline btn-sm w-100" style="text-align: left; margin-bottom: 4px; border:none;">{{ $router->name }}</button>
                    </form>
                </li>
                @endforeach
            </ul>
        </div>
        @endif
        @can('create pppoe')
        <a href="{{ route('pppoe.create') }}" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>
            Tambah User
        </a>
        @endcan
    </div>
</div>

<div class="card mb-24">
    <form action="{{ route('pppoe.index') }}" method="GET" class="d-flex gap-12 align-center">
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
                    <th>Username</th>
                    <th>Password</th>
                    <th>Router</th>
                    <th>Profile</th>
                    <th>IP Address</th>
                    <th>Status</th>
                    <th width="150">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pppoeUsers as $user)
                <tr>
                    <td>
                        <div class="font-bold">{{ $user->username }}</div>
                        @if($user->customer)
                        <div class="text-sm text-muted">{{ $user->customer->name }}</div>
                        @endif
                    </td>
                    <td class="font-mono text-sm">{{ $user->password }}</td>
                    <td>{{ $user->router->name ?? '-' }}</td>
                    <td>
                        <span class="badge badge-info">{{ $user->profile }}</span>
                    </td>
                    <td class="font-mono text-sm">
                        {{ $user->remote_address ?? 'Dynamic' }}
                    </td>
                    <td>
                        <span class="badge {{ $user->is_disabled ? 'badge-danger' : 'badge-success' }}">
                            {{ $user->is_disabled ? 'Disabled' : 'Active' }}
                        </span>
                        @if(!$user->mikrotik_id)
                        <span class="badge badge-warning" title="Belum sinkron ke Mikrotik">⚠ Unsynced</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-8">
                            @can('edit pppoe')
                            <form action="{{ route('pppoe.toggle', $user) }}" method="POST" style="margin: 0;">
                                @csrf
                                <button type="submit" class="btn {{ $user->is_disabled ? 'btn-success' : 'btn-warning' }} btn-sm" title="{{ $user->is_disabled ? 'Enable' : 'Disable' }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        @if($user->is_disabled)
                                        <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                                        @else
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="9" x2="15" y2="15"/><line x1="15" y1="9" x2="9" y2="15"/>
                                        @endif
                                    </svg>
                                </button>
                            </form>
                            <a href="{{ route('pppoe.edit', $user) }}" class="btn btn-outline btn-sm">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </a>
                            @endcan
                            @can('delete pppoe')
                            <form action="{{ route('pppoe.destroy', $user) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Hapus user PPPoE {{ $user->username }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
                                </button>
                            </form>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 40px;">
                        <div class="empty-state">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                            <h3>Belum ada user PPPoE</h3>
                            <p>Tambahkan secara manual atau sync dari router</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($pppoeUsers->hasPages())
    <div style="margin-top: 20px;">
        {{ $pppoeUsers->links('pagination::bootstrap-4') }}
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
// Simple dropdown toggle for bootstrap-less template
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
