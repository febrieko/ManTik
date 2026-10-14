@extends('layouts.app')

@section('title', 'Manajemen Pengguna')
@section('page-title', 'Daftar Pengguna')

@section('content')
<div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
    <div class="page-header-title">
        <h2>Manajemen Pengguna & Hak Akses</h2>
        <p>Kelola akun Super Admin, Admin, dan Teknisi serta hak akses perangkat dan kontak notifikasi</p>
    </div>
    <div class="d-flex gap-8">
        <a href="{{ route('users.create') }}" class="btn btn-primary btn-lg" style="box-shadow: 0 4px 15px rgba(0, 212, 255, 0.3);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
            Tambah Pengguna Baru
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success mb-24" style="background:rgba(0,230,118,0.1);border:1px solid rgba(0,230,118,0.3);color:#00e676;padding:12px 16px;border-radius:10px;display:flex;align-items:center;gap:10px;">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px;flex-shrink:0;"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    <span>{{ session('success') }}</span>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger mb-24" style="background:rgba(255,68,68,0.1);border:1px solid rgba(255,68,68,0.3);color:#ff4444;padding:12px 16px;border-radius:10px;display:flex;align-items:center;gap:10px;">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px;flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
    <span>{{ session('error') }}</span>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger mb-24" style="background:rgba(255,68,68,0.1);border:1px solid rgba(255,68,68,0.3);color:#ff4444;padding:12px 16px;border-radius:10px;">
    <strong>Terjadi kesalahan:</strong>
    <ul style="margin:6px 0 0 18px;">
        @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
        @endforeach
    </ul>
</div>
@endif

{{-- Filter Card --}}
<div class="card mb-20" style="padding: 16px 20px;">
    <form action="{{ route('users.index') }}" method="GET" style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 240px; position: relative;">
            <input type="text" name="search" class="form-control" placeholder="Cari nama, email, atau telepon..." value="{{ request('search') }}" style="padding-left: 36px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px; position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted);">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
        </div>
        <div style="width: 200px;">
            <select name="role" class="form-control" onchange="this.form.submit()">
                <option value="">Semua Hak Akses</option>
                <option value="super-admin" {{ request('role') == 'super-admin' ? 'selected' : '' }}>Super Admin</option>
                <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Administrator</option>
                <option value="teknisi" {{ request('role') == 'teknisi' ? 'selected' : '' }}>Teknisi</option>
            </select>
        </div>
        <button type="submit" class="btn btn-outline">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            Filter
        </button>
        @if(request('search') || request('role'))
        <a href="{{ route('users.index') }}" class="btn btn-outline" style="color: var(--text-muted);">Reset</a>
        @endif
    </form>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Pengguna</th>
                    <th>Email</th>
                    <th>Hak Akses (Role)</th>
                    <th>Kontak Notifikasi</th>
                    <th>Status</th>
                    <th width="260" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 1.5px solid var(--border);">
                            <div>
                                <div class="font-bold" style="color: var(--text-primary); font-size: 14px;">
                                    {{ $user->name }}
                                    @if($user->id === auth()->id())
                                        <span class="badge" style="background: rgba(0,212,255,0.15); color: var(--primary); font-size: 10px; margin-left: 4px;">Anda</span>
                                    @endif
                                </div>
                                <div style="font-size: 11px; color: var(--text-muted);">Terdaftar: {{ $user->created_at ? $user->created_at->format('d M Y') : '-' }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span style="font-family: monospace; font-size: 13px;">{{ $user->email }}</span>
                    </td>
                    <td>
                        @php
                            $roleName = $user->roles->first()?->name;
                        @endphp
                        @if($roleName === 'super-admin')
                            <span class="badge" style="background: rgba(255, 68, 68, 0.15); color: #ff5252; border: 1px solid rgba(255, 68, 68, 0.3);">👑 Super Admin</span>
                        @elseif($roleName === 'admin')
                            <span class="badge" style="background: rgba(0, 212, 255, 0.15); color: #00d4ff; border: 1px solid rgba(0, 212, 255, 0.3);">🛡️ Administrator</span>
                        @elseif($roleName === 'teknisi')
                            <span class="badge" style="background: rgba(255, 179, 0, 0.15); color: #ffb300; border: 1px solid rgba(255, 179, 0, 0.3);">🔧 Teknisi</span>
                        @else
                            <span class="badge badge-muted">{{ $roleName ?? 'User' }}</span>
                        @endif
                    </td>
                    <td>
                        <div style="font-size:12px;display:flex;flex-direction:column;gap:3px;">
                            <div>
                                <span style="color:#25d366;font-weight:600;font-size:11px;">WA:</span>
                                @if($user->phone)
                                    <span style="font-family:monospace;">{{ $user->phone }}</span>
                                @else
                                    <span class="text-muted" style="font-style:italic;font-size:11px;">Belum diatur</span>
                                @endif
                            </div>
                            <div>
                                <span style="color:#00b0ff;font-weight:600;font-size:11px;">TG:</span>
                                @if($user->telegram_chat_id)
                                    <span style="font-family:monospace;">{{ $user->telegram_chat_id }}</span>
                                @else
                                    <span class="text-muted" style="font-style:italic;font-size:11px;">Belum diatur</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>
                        @if($user->is_active)
                            <span class="badge badge-success">Aktif</span>
                        @else
                            <span class="badge badge-danger">Nonaktif</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-6 justify-center align-center" style="flex-wrap: wrap;">
                            {{-- Tombol Ubah Password --}}
                            <button type="button" class="btn btn-outline btn-sm" 
                                    onclick="openChangePasswordModal('{{ $user->id }}', '{{ addslashes($user->name) }}')"
                                    title="Ubah Password Pengguna"
                                    style="border-color: rgba(255,179,0,0.3); color: #ffb300;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                                Password
                            </button>

                            {{-- Tombol Edit Profil/Hak Akses --}}
                            <a href="{{ route('users.edit', $user->id) }}" class="btn btn-outline btn-sm" title="Edit Data & Hak Akses">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                Edit
                            </a>

                            {{-- Tombol Akses Router (Khusus Teknisi) --}}
                            @if($user->isTeknisi())
                            <a href="{{ route('users.router-assignments', $user) }}" class="btn btn-outline btn-sm" title="Atur Akses Router">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                Router
                            </a>
                            @endif

                            {{-- Tombol Hapus (Jika bukan diri sendiri) --}}
                            @if($user->id !== auth()->id())
                            <form action="{{ route('users.destroy', $user->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengguna {{ addslashes($user->name) }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline btn-sm" title="Hapus Pengguna" style="color: #ff5252; border-color: rgba(255,82,82,0.2);">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 40px; color: var(--text-muted);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 48px; height: 48px; margin-bottom: 12px; opacity: 0.5;">
                            <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
                        </svg>
                        <div>Tidak ada data pengguna yang sesuai dengan pencarian.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($users->hasPages())
    <div style="padding:16px 20px;border-top:1px solid var(--border);">
        {{ $users->links() }}
    </div>
    @endif
</div>

{{-- MODAL UBAH PASSWORD PENGGUNA --}}
<div id="modal-change-password" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:9999;align-items:center;justify-content:center;backdrop-filter:blur(4px);">
    <div class="card" style="width:100%;max-width:440px;background:var(--bg-secondary);border:1px solid var(--border);border-radius:12px;box-shadow:var(--shadow-card);margin:20px;">
        <div class="card-header d-flex justify-between align-center" style="border-bottom:1px solid var(--border);padding:16px 20px;">
            <div class="card-title" style="margin: 0;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:#ffb300;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                <span id="pw-modal-title">Ubah Password Pengguna</span>
            </div>
            <button type="button" onclick="closeChangePasswordModal()" style="background:transparent;border:none;color:var(--text-secondary);cursor:pointer;font-size:20px;line-height:1;">&times;</button>
        </div>
        <form id="form-change-password" method="POST" action="">
            @csrf
            @method('PUT')
            <div style="padding:20px;">
                <div style="margin-bottom: 16px; padding: 10px 14px; background: rgba(0, 212, 255, 0.05); border: 1px solid rgba(0, 212, 255, 0.2); border-radius: 8px; font-size: 13px;">
                    Mengubah password untuk: <strong id="pw-modal-user" style="color: var(--primary);"></strong>
                </div>

                <div class="form-group mb-16">
                    <label class="form-label" for="modal_new_password">Password Baru *</label>
                    <input type="password" name="password" id="modal_new_password" class="form-control" placeholder="Minimal 6 karakter" required minlength="6">
                </div>

                <div class="form-group mb-8">
                    <label class="form-label" for="modal_new_password_confirmation">Ulangi Password Baru *</label>
                    <input type="password" name="password_confirmation" id="modal_new_password_confirmation" class="form-control" placeholder="Konfirmasi password baru" required minlength="6">
                </div>
            </div>
            <div class="d-flex justify-end gap-8" style="padding:16px 20px;border-top:1px solid var(--border);background:rgba(255,255,255,0.01);">
                <button type="button" class="btn btn-outline" onclick="closeChangePasswordModal()">Batal</button>
                <button type="submit" class="btn btn-warning">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><polyline points="20 6 9 17 4 12"/></svg>
                    Simpan Password Baru
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openChangePasswordModal(userId, userName) {
    document.getElementById('pw-modal-title').innerText = 'Ubah Password';
    document.getElementById('pw-modal-user').innerText = userName;
    document.getElementById('modal_new_password').value = '';
    document.getElementById('modal_new_password_confirmation').value = '';
    document.getElementById('form-change-password').action = '/users/' + userId + '/password';
    
    const modal = document.getElementById('modal-change-password');
    modal.style.display = 'flex';
}

function closeChangePasswordModal() {
    const modal = document.getElementById('modal-change-password');
    modal.style.display = 'none';
}

// Close modal on click outside
window.addEventListener('click', function(event) {
    const modalPw = document.getElementById('modal-change-password');
    if (event.target === modalPw) {
        closeChangePasswordModal();
    }
});
</script>
@endpush
@endsection
