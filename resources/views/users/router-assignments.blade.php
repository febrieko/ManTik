@extends('layouts.app')

@section('title', 'Manajemen Akses Router')
@section('page-title', 'Akses Router: ' . $user->name)
@section('breadcrumb')
    <a href="{{ route('users.index') }}">User Management</a>
    <span class="separator">/</span>
    <span class="current">Akses Router</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-title">
        <h2>Manajemen Akses Router</h2>
        <p>Atur router mana saja yang dapat diakses oleh teknisi ini</p>
    </div>
    <a href="{{ route('users.index') }}" class="btn btn-outline">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Kembali ke Daftar User
    </a>
</div>

<div class="grid" style="grid-template-columns: 2fr 1fr; gap: 24px;">
    <!-- Daftar Router yang Di-assign -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Router yang Di-assign</div>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Router</th>
                        <th>Host</th>
                        <th>Assigned By</th>
                        <th>Catatan</th>
                        <th>Tgl Assign</th>
                        <th width="100">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assignedRouters as $router)
                    <tr>
                        <td class="font-bold text-success">{{ $router->name }}</td>
                        <td class="font-mono text-sm">{{ $router->host }}</td>
                        <td>{{ $router->pivot->assignedBy->name ?? 'System' }}</td>
                        <td class="text-muted">{{ $router->pivot->note ?? '-' }}</td>
                        <td class="text-sm">{{ $router->pivot->created_at->format('d M Y H:i') }}</td>
                        <td>
                            <form action="{{ route('users.router-assignments.revoke', [$user, $router]) }}" method="POST" onsubmit="return confirm('Cabut akses ke router {{ $router->name }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" title="Cabut Akses">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
                                    Cabut
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center" style="padding: 40px;">
                            <div class="empty-state">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                <h3>Belum ada akses router</h3>
                                <p>Teknisi ini belum memiliki akses ke router manapun.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Form Tambah Akses -->
    <div class="card" style="height: fit-content;">
        <div class="card-header">
            <div class="card-title">Tambah Akses Router</div>
        </div>

        <form action="{{ route('users.router-assignments.assign', $user) }}" method="POST">
            @csrf
            
            <div class="form-group">
                <label class="form-label" for="router_id">Pilih Router</label>
                <select name="router_id" id="router_id" class="form-control" required>
                    <option value="">-- Pilih Router --</option>
                    @foreach($allRouters as $r)
                        @if(!$assignedRouters->contains('id', $r->id))
                            <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->host }})</option>
                        @endif
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="note">Catatan (Opsional)</label>
                <input type="text" name="note" id="note" class="form-control" placeholder="Contoh: Router khusus wilayah timur">
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                Berikan Akses
            </button>
        </form>
    </div>
</div>
@endsection
