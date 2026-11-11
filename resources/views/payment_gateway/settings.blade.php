@extends('layouts.app')

@section('title', 'Pengaturan Payment Gateway')
@section('page-title', 'Integrasi Payment Gateway')

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
    <div class="page-header-title">
        <h2>Pengaturan Payment Gateway</h2>
        <p>Sambungkan top-up saldo pelanggan ke Tripay, Midtrans, Duitku, atau Xendit agar saldo terisi otomatis</p>
    </div>
    <div>
        @if($isActive)
            <span style="padding:6px 14px;border-radius:999px;background:rgba(37,211,102,0.15);color:#25d366;border:1px solid rgba(37,211,102,0.35);font-weight:600;font-size:13px;">
                ● Gateway Aktif ({{ strtoupper($config['provider']) }} - {{ strtoupper($config['mode']) }})
            </span>
        @else
            <span style="padding:6px 14px;border-radius:999px;background:rgba(245,158,11,0.15);color:#f59e0b;border:1px solid rgba(245,158,11,0.35);font-weight:600;font-size:13px;">
                ● Mode Manual (Konfirmasi Admin)
            </span>
        @endif
    </div>
</div>

@if(session('success'))
<div class="alert alert-success mb-20" style="background:rgba(0,230,118,0.1);border:1px solid rgba(0,230,118,0.3);color:#00e676;padding:12px 16px;border-radius:10px;">
    {{ session('success') }}
</div>
@endif

@if($errors->any())
<div class="alert alert-danger mb-20" style="background:rgba(255,68,68,0.1);border:1px solid rgba(255,68,68,0.3);color:#ff4444;padding:12px 16px;border-radius:10px;">
    <ul style="margin:0;padding-left:18px;">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:20px;align-items:start;">
    <div class="card" style="padding:24px;">
        <h3 style="margin-top:0;margin-bottom:16px;">Konfigurasi Provider</h3>

        <form action="{{ route('payment-gateway.settings.update') }}" method="POST">
            @csrf

            <div class="form-group mb-16" style="display:flex;align-items:center;gap:10px;">
                <input type="checkbox" name="enabled" id="pgEnabled" value="1" {{ old('enabled', $config['enabled']) ? 'checked' : '' }} style="width:18px;height:18px;">
                <label for="pgEnabled" style="margin:0;font-weight:600;cursor:pointer;">Aktifkan Pembayaran Otomatis via Payment Gateway</label>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;" class="mb-16">
                <div class="form-group">
                    <label class="form-label">Provider Payment Gateway</label>
                    <select name="provider" class="form-control">
                        <option value="manual" {{ old('provider', $config['provider']) === 'manual' ? 'selected' : '' }}>Manual (Transfer + Approve Admin)</option>
                        <option value="tripay" {{ old('provider', $config['provider']) === 'tripay' ? 'selected' : '' }}>Tripay</option>
                        <option value="midtrans" {{ old('provider', $config['provider']) === 'midtrans' ? 'selected' : '' }}>Midtrans Snap</option>
                        <option value="duitku" {{ old('provider', $config['provider']) === 'duitku' ? 'selected' : '' }}>Duitku</option>
                        <option value="xendit" {{ old('provider', $config['provider']) === 'xendit' ? 'selected' : '' }}>Xendit Invoice</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Environment Mode</label>
                    <select name="mode" class="form-control">
                        <option value="sandbox" {{ old('mode', $config['mode']) === 'sandbox' ? 'selected' : '' }}>Sandbox / Testing</option>
                        <option value="production" {{ old('mode', $config['mode']) === 'production' ? 'selected' : '' }}>Production / Live</option>
                    </select>
                </div>
            </div>

            <div class="form-group mb-16">
                <label class="form-label">Merchant Code / Store ID</label>
                <input type="text" name="merchant_code" class="form-control" value="{{ old('merchant_code', $config['merchant_code']) }}"
                       placeholder="Wajib untuk Tripay & Duitku">
            </div>

            <div class="form-group mb-16">
                <label class="form-label">API Key / Server Key</label>
                <input type="password" name="api_key" id="pgApiKey" class="form-control" value="{{ old('api_key', $config['api_key']) }}"
                       placeholder="API Key (Tripay/Duitku) / Server Key (Midtrans) / Secret Key (Xendit)">
                <small style="color:var(--text-muted);">
                    <a href="javascript:void(0)" onclick="toggleSecret('pgApiKey')" style="color:#00d4ff;">Tampilkan/Sembunyikan</a>
                </small>
            </div>

            <div class="form-group mb-16">
                <label class="form-label">Private Key / Callback Token</label>
                <input type="password" name="private_key" id="pgPrivateKey" class="form-control" value="{{ old('private_key', $config['private_key']) }}"
                       placeholder="Private Key (Tripay) / Callback Verification Token (Xendit)">
                <small style="color:var(--text-muted);">
                    <a href="javascript:void(0)" onclick="toggleSecret('pgPrivateKey')" style="color:#00d4ff;">Tampilkan/Sembunyikan</a>
                </small>
            </div>

            <div class="form-group mb-24">
                <label class="form-label">Default Channel Pembayaran</label>
                <input type="text" name="default_channel" class="form-control" value="{{ old('default_channel', $config['default_channel']) }}"
                       placeholder="Contoh: QRIS, BRIVA, BCAVA, OVO, SP">
                <small style="color:var(--text-muted);">Digunakan sebagai metode default pada Tripay (misal <code>QRIS</code>) atau Duitku (misal <code>SP</code> / <code>BC</code>).</small>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;">
                💾 Simpan Konfigurasi Payment Gateway
            </button>
        </form>
    </div>

    <div class="card" style="padding:24px;">
        <h3 style="margin-top:0;margin-bottom:12px;">🔗 Endpoint Webhook / Callback</h3>
        <p style="font-size:13px;color:var(--text-muted);margin-bottom:16px;">
            Salin URL callback di bawah ini ke dashboard merchant provider yang Anda gunakan agar pembayaran top-up otomatis memverifikasi & mengisi saldo pelanggan.
        </p>

        @foreach($callbackUrls as $prov => $url)
        <div style="margin-bottom:14px;">
            <div style="font-size:12px;font-weight:700;text-transform:uppercase;color:#00d4ff;margin-bottom:4px;">{{ $prov }}</div>
            <div style="display:flex;gap:8px;">
                <input type="text" class="form-control" value="{{ $url }}" readonly style="font-family:monospace;font-size:12px;">
                <button type="button" class="btn btn-sm btn-outline" onclick="navigator.clipboard.writeText('{{ $url }}')">Salin</button>
            </div>
        </div>
        @endforeach

        <div style="margin-top:18px;padding:12px 14px;border-radius:10px;background:rgba(0,212,255,0.08);border:1px solid rgba(0,212,255,0.2);font-size:13px;color:var(--text-muted);">
            <strong style="color:var(--text-primary);">Catatan Verifikasi Callback:</strong>
            <ul style="margin:6px 0 0;padding-left:18px;">
                <li><strong>Tripay:</strong> memvalidasi header <code>X-Callback-Signature</code> memakai Private Key.</li>
                <li><strong>Midtrans:</strong> memvalidasi <code>signature_key</code> SHA-512 memakai Server Key.</li>
                <li><strong>Duitku:</strong> memvalidasi parameter <code>signature</code> MD5 memakai API Key.</li>
                <li><strong>Xendit:</strong> memvalidasi header <code>x-callback-token</code> memakai Callback Token (diisi pada Private Key).</li>
            </ul>
        </div>
    </div>
</div>

<script>
function toggleSecret(id) {
    const el = document.getElementById(id);
    if (el) {
        el.type = el.type === 'password' ? 'text' : 'password';
    }
}
</script>
@endsection

