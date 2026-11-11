@extends('layouts.app')

@section('title', 'Tambah Pelanggan Baru')
@section('page-title', 'Tambah Pelanggan')

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
    <div class="page-header-title">
        <h2>Tambah Pelanggan Baru</h2>
        <p>Daftarkan pelanggan baru dengan nomor WhatsApp unik, saldo awal, dan opsi akun login portal</p>
    </div>
    <a href="{{ route('customers.index') }}" class="btn btn-outline">
        &larr; Kembali ke Daftar
    </a>
</div>

@if($errors->any())
<div class="alert alert-danger mb-24" style="background:rgba(255,68,68,0.1);border:1px solid rgba(255,68,68,0.3);color:#ff4444;padding:12px 16px;border-radius:10px;">
    <strong>Periksa kembali input Anda:</strong>
    <ul style="margin:6px 0 0 18px;">
        @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
        @endforeach
    </ul>
</div>
@endif

<form action="{{ route('customers.store') }}" method="POST">
    @csrf
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:20px;">
        {{-- Kolom Kiri: Data Utama --}}
        <div class="card" style="padding:24px;">
            <h3 style="margin-bottom:16px;font-size:16px;">Informasi Pelanggan</h3>

            <div class="form-group mb-16">
                <label class="form-label">Nama Lengkap <span style="color:#ef4444;">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" class="form-control" required placeholder="Contoh: Budi Santoso">
            </div>

            <div class="form-group mb-16">
                <label class="form-label">Nomor WhatsApp (Unik per Pelanggan)</label>
                <input type="text" name="phone" value="{{ old('phone') }}" class="form-control" placeholder="Contoh: 081234567890 atau 6281234567890">
                <small style="color:var(--text-muted);font-size:11px;">Otomatis dinormalisasi ke format 62... Digunakan untuk verifikasi beli voucher WA &amp; login portal.</small>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;" class="mb-16">
                <div class="form-group">
                    <label class="form-label">Tipe Pelanggan</label>
                    <select name="customer_type" class="form-control">
                        <option value="regular" {{ old('customer_type', 'regular') === 'regular' ? 'selected' : '' }}>Regular (PPPoE / Umum)</option>
                        <option value="reseller" {{ old('customer_type') === 'reseller' ? 'selected' : '' }}>Reseller / Agen Voucher</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Status Akun</label>
                    <select name="status" class="form-control">
                        <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Non-Aktif</option>
                        <option value="suspended" {{ old('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    </select>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;" class="mb-16">
                <div class="form-group">
                    <label class="form-label">Email (Opsional)</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="email@contoh.com">
                </div>
                <div class="form-group">
                    <label class="form-label">NIK / No. KTP (Opsional)</label>
                    <input type="text" name="nik" value="{{ old('nik') }}" class="form-control" placeholder="35xxxxxxxxxxxxxx">
                </div>
            </div>

            <div class="form-group mb-16">
                <label class="form-label">Telegram Chat ID (Opsional)</label>
                <input type="text" name="telegram_chat_id" value="{{ old('telegram_chat_id') }}" class="form-control" placeholder="Contoh: 123456789">
            </div>

            <div class="form-group">
                <label class="form-label">Alamat Lengkap (Opsional)</label>
                <textarea name="address" rows="3" class="form-control" placeholder="Alamat pemasangan atau domisili...">{{ old('address') }}</textarea>
            </div>
        </div>

        {{-- Kolom Kanan: Portal & Saldo Awal --}}
        <div style="display:flex;flex-direction:column;gap:20px;">
            <div class="card" style="padding:24px;">
                <h3 style="margin-bottom:12px;font-size:16px;">Akun Login Portal Pelanggan</h3>
                <p style="font-size:12px;color:var(--text-muted);margin-bottom:16px;">
                    Pelanggan dapat login ke portal menggunakan <strong>Nomor WhatsApp</strong> dan <strong>Password</strong> yang ditentukan di sini.
                </p>

                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;margin-bottom:16px;">
                    <input type="checkbox" name="create_portal_account" id="create_portal_account" value="1" {{ old('create_portal_account') ? 'checked' : '' }} onchange="togglePortalPassword()">
                    <span style="font-weight:600;font-size:13px;">Buatkan Akun Login Portal untuk pelanggan ini</span>
                </label>

                <div id="portal_password_box" style="{{ old('create_portal_account') ? '' : 'display:none;' }}">
                    <div class="form-group">
                        <label class="form-label">Password Portal <span style="color:#ef4444;">*</span></label>
                        <input type="password" name="portal_password" id="portal_password" class="form-control" placeholder="Minimal 6 karakter" autocomplete="new-password">
                    </div>
                </div>
            </div>

            <div class="card" style="padding:24px;">
                <h3 style="margin-bottom:12px;font-size:16px;">Saldo Deposit Awal (Opsional)</h3>
                <div class="form-group mb-12">
                    <label class="form-label">Nominal Saldo Awal (Rp)</label>
                    <input type="number" name="initial_balance" value="{{ old('initial_balance', 0) }}" min="0" step="500" class="form-control" placeholder="0">
                    <small style="color:var(--text-muted);font-size:11px;">Jika diisi &gt; 0, sistem otomatis mencatatnya sebagai top-up awal di buku mutasi saldo.</small>
                </div>
                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                    <input type="checkbox" name="send_wa_topup" value="1" {{ old('send_wa_topup') ? 'checked' : '' }}>
                    <span style="font-size:13px;">Kirim notifikasi saldo awal ke WhatsApp pelanggan</span>
                </label>
            </div>

            <div style="display:flex;gap:12px;justify-content:flex-end;">
                <a href="{{ route('customers.index') }}" class="btn btn-outline">Batal</a>
                <button type="submit" class="btn btn-primary btn-lg">Simpan Pelanggan</button>
            </div>
        </div>
    </div>
</form>

<script>
function togglePortalPassword() {
    const checked = document.getElementById('create_portal_account').checked;
    document.getElementById('portal_password_box').style.display = checked ? 'block' : 'none';
}
</script>
@endsection

