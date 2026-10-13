@extends('layouts.app')

@section('title', 'Manajemen Users')
@section('page-title', 'Daftar User')

@section('content')
<div class="page-header">
    <div class="page-header-title">
        <h2>Manajemen Users & Kontak Notifikasi</h2>
        <p>Kelola admin, teknisi, hak akses router, serta kontak WhatsApp dan Telegram penerima notifikasi</p>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success mb-24" style="background:rgba(0,230,118,0.1);border:1px solid rgba(0,230,118,0.3);color:#00e676;padding:12px 16px;border-radius:10px;display:flex;align-items:center;gap:10px;">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px;"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    <span>{{ session('success') }}</span>
</div>
@endif

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Nama User</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Kontak Notifikasi (WA & TG)</th>
                    <th>Status</th>
                    <th width="180" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr>
                    <td class="font-bold">{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        @foreach($user->roles as $role)
                            <span class="badge" style="background: rgba(0, 212, 255, 0.1); color: #00d4ff;">{{ $role->name }}</span>
                        @endforeach
                    </td>
                    <td>
                        <div style="font-size:12px;display:flex;flex-direction:column;gap:4px;">
                            <div>
                                <span style="color:#25d366;font-weight:600;">WA:</span>
                                @if($user->phone)
                                    <span style="font-family:monospace;">{{ $user->phone }}</span>
                                @else
                                    <span class="text-muted" style="font-style:italic;">Belum diatur</span>
                                @endif
                            </div>
                            <div>
                                <span style="color:#00b0ff;font-weight:600;">TG:</span>
                                @if($user->telegram_chat_id)
                                    <span style="font-family:monospace;">{{ $user->telegram_chat_id }}</span>
                                @else
                                    <span class="text-muted" style="font-style:italic;">Belum diatur</span>
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
                        <div class="d-flex gap-8 justify-center align-center">
                            <button type="button" class="btn btn-outline btn-sm" 
                                    onclick="openEditContactModal('{{ $user->id }}', '{{ addslashes($user->name) }}', '{{ $user->phone }}', '{{ $user->telegram_chat_id }}', {{ $user->is_active ? 1 : 0 }})"
                                    title="Edit Kontak Notifikasi">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;margin-right:3px;"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                Kontak
                            </button>
                            @if($user->isTeknisi())
                            <a href="{{ route('users.router-assignments', $user) }}" class="btn btn-outline btn-sm" title="Atur Akses Router">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;margin-right:3px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                Router
                            </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    @if($users->hasPages())
    <div style="padding:16px 20px;border-top:1px solid var(--border);">
        {{ $users->links() }}
    </div>
    @endif
</div>

{{-- Modal Edit Kontak --}}
<div id="modal-edit-contact" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:9999;align-items:center;justify-content:center;">
    <div class="card" style="width:100%;max-width:480px;background:var(--bg-secondary);border:1px solid var(--border);border-radius:12px;box-shadow:var(--shadow-card);margin:20px;">
        <div class="card-header d-flex justify-between align-center" style="border-bottom:1px solid var(--border);padding:16px 20px;">
            <h3 style="font-size:16px;font-weight:600;color:var(--text-primary);" id="modal-title">Edit Kontak Notifikasi</h3>
            <button type="button" onclick="closeEditContactModal()" style="background:transparent;border:none;color:var(--text-secondary);cursor:pointer;font-size:18px;">&times;</button>
        </div>
        <form id="form-edit-contact" method="POST" action="">
            @csrf
            @method('PUT')
            <div style="padding:20px;">
                <div class="form-group mb-16">
                    <label class="form-label">Nomor WhatsApp (Format 62...):</label>
                    <input type="text" name="phone" id="modal-phone" class="form-control" placeholder="Contoh: 6281234567890">
                    <small class="text-xs text-muted">Akan menerima notifikasi jika router mati/menyala.</small>
                </div>
                <div class="form-group mb-16">
                    <label class="form-label">Telegram Chat ID:</label>
                    <input type="text" name="telegram_chat_id" id="modal-tg" class="form-control" placeholder="Contoh: 123456789">
                    <small class="text-xs text-muted">Dapatkan dari bot @userinfobot di Telegram.</small>
                </div>
                <div class="form-group mb-8">
                    <label class="d-flex align-center gap-8 cursor-pointer">
                        <input type="checkbox" name="is_active" id="modal-active" value="1">
                        <span>User Aktif</span>
                    </label>
                </div>
            </div>
            <div class="d-flex justify-end gap-8" style="padding:16px 20px;border-top:1px solid var(--border);background:rgba(255,255,255,0.01);">
                <button type="button" class="btn btn-outline" onclick="closeEditContactModal()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Kontak</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openEditContactModal(userId, userName, phone, tg, isActive) {
    document.getElementById('modal-title').innerText = 'Kontak Notifikasi: ' + userName;
    document.getElementById('modal-phone').value = phone || '';
    document.getElementById('modal-tg').value = tg || '';
    document.getElementById('modal-active').checked = (isActive == 1);
    document.getElementById('form-edit-contact').action = '/users/' + userId;
    
    const modal = document.getElementById('modal-edit-contact');
    modal.style.display = 'flex';
}

function closeEditContactModal() {
    const modal = document.getElementById('modal-edit-contact');
    modal.style.display = 'none';
}
</script>
@endpush
@endsection
