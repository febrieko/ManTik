@extends('layouts.app')

@section('title', 'Manajemen Paket Layanan')
@section('page-title', 'Paket Layanan')

@section('content')
<div class="page-header">
    <div class="page-header-title">
        <h2>Daftar Paket Layanan</h2>
        <p>Kelola profil paket untuk PPPoE dan Hotspot</p>
    </div>
    <div style="display: flex; gap: 12px;">
        <button type="button" class="btn btn-outline" onclick="document.getElementById('syncModal').classList.add('show')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 2v6h-6"/><path d="M3 12a9 9 0 1015-6.7L21 8"/><path d="M3 22v-6h6"/><path d="M21 12A9 9 0 106 18.7L3 16"/></svg>
            Sync dari MikroTik
        </button>
        <a href="{{ route('packages.create') }}" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Tambah Paket
        </a>
    </div>
</div>

<div class="card">
    <div style="display: flex; gap: 12px; margin-bottom: 24px;">
        <a href="{{ route('packages.index') }}" class="btn {{ !$type ? 'btn-primary' : 'btn-outline' }}">Semua</a>
        <a href="{{ route('packages.index', ['type' => 'pppoe']) }}" class="btn {{ $type == 'pppoe' ? 'btn-primary' : 'btn-outline' }}">PPPoE</a>
        <a href="{{ route('packages.index', ['type' => 'hotspot']) }}" class="btn {{ $type == 'hotspot' ? 'btn-primary' : 'btn-outline' }}">Hotspot</a>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Nama Paket</th>
                    <th>Tipe</th>
                    <th>Harga</th>
                    <th>Durasi</th>
                    <th>Bandwidth (Up/Down)</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($packages as $package)
                <tr>
                    <td>
                        <strong>{{ $package->name }}</strong>
                        <div style="font-size: 11px; color: #8892b0;">Slug: {{ $package->slug }}</div>
                    </td>
                    <td>
                        @if($package->type == 'pppoe')
                            <span class="badge" style="background: rgba(0, 212, 255, 0.1); color: #00d4ff;">PPPoE</span>
                        @else
                            <span class="badge" style="background: rgba(255, 171, 0, 0.1); color: #ffab00;">Hotspot</span>
                        @endif
                    </td>
                    <td>Rp {{ number_format($package->price, 0, ',', '.') }}</td>
                    <td>{{ $package->duration_days }} Hari</td>
                    <td>{{ $package->bandwidth_label }}</td>
                    <td>
                        <form action="{{ route('packages.toggle', $package) }}" method="POST" style="display: inline-block;">
                            @csrf
                            <button type="submit" class="badge" style="border: none; cursor: pointer; background: {{ $package->is_active ? 'rgba(0, 230, 118, 0.1)' : 'rgba(255, 61, 113, 0.1)' }}; color: {{ $package->is_active ? '#00e676' : '#ff3d71' }};">
                                {{ $package->is_active ? 'Aktif' : 'Nonaktif' }}
                            </button>
                        </form>
                    </td>
                    <td>
                        <div style="display: flex; gap: 8px;">
                            <a href="{{ route('packages.edit', $package) }}" class="btn btn-outline" style="padding: 6px; height: auto;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </a>
                            <form action="{{ route('packages.destroy', $package) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus paket ini?');" style="display: inline-block;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline" style="padding: 6px; height: auto; color: #ff3d71; border-color: rgba(255,61,113,0.3);">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 48px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="width: 48px; height: 48px; color: #4b5563; margin-bottom: 16px;"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
                        <p style="color: #8892b0;">Belum ada paket layanan yang dibuat.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($packages->hasPages())
    <div style="margin-top: 24px;">
        {{ $packages->links() }}
    </div>
    @endif
</div>

{{-- Modal Sync --}}
<div class="modal" id="syncModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('packages.sync-router') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h3 class="modal-title">Sync Profile dari MikroTik</h3>
                    <button type="button" class="close-modal" onclick="document.getElementById('syncModal').classList.remove('show')">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="sync_router_id">Pilih Router</label>
                        <select name="router_id" id="sync_router_id" class="form-control" required>
                            @foreach($routers as $router)
                            <option value="{{ $router->id }}">{{ $router->name }} ({{ $router->host }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="sync_type">Tipe Layanan</label>
                        <select name="type" id="sync_type" class="form-control" required>
                            <option value="pppoe">PPPoE Profile</option>
                            <option value="hotspot">Hotspot User Profile</option>
                        </select>
                    </div>
                    <p style="font-size: 13px; color: #8892b0; line-height: 1.6; margin-top: 12px;">
                        Data profile (name) dari MikroTik akan ditambahkan sebagai Paket baru di MANTIK. Harga dan Durasi default akan diset.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('syncModal').classList.remove('show')">Batal</button>
                    <button type="submit" class="btn btn-primary">Mulai Sync</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
.modal.show { display: flex; }
.modal-dialog { width: 100%; max-width: 500px; padding: 20px; }
.modal-content { background: #112240; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); overflow: hidden; }
.modal-header { padding: 20px 24px; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center; }
.modal-title { margin: 0; font-size: 16px; font-weight: 600; }
.close-modal { background: none; border: none; color: #8892b0; font-size: 24px; cursor: pointer; line-height: 1; }
.close-modal:hover { color: #ccd6f6; }
.modal-body { padding: 24px; }
.modal-footer { padding: 16px 24px; border-top: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: flex-end; gap: 12px; }
</style>
@endsection
