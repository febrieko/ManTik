<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beli Voucher WiFi Langsung - {{ $brandName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: #080d1a;
            color: #e8eaf6;
            min-height: 100vh;
            padding: 24px 16px 48px;
            position: relative;
        }
        .bg-grid {
            position: fixed; inset: 0;
            background-image:
                linear-gradient(rgba(37, 211, 102, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 212, 255, 0.03) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
        }
        .container {
            position: relative; z-index: 1;
            max-width: 780px; margin: 0 auto;
        }
        .header {
            text-align: center; margin-bottom: 26px;
        }
        .badge-top {
            display: inline-block; padding: 5px 12px; border-radius: 999px;
            background: rgba(0, 212, 255, 0.12); border: 1px solid rgba(0, 212, 255, 0.3);
            color: #38bdf8; font-size: 12px; font-weight: 700; margin-bottom: 12px;
        }
        .header h1 { font-size: 26px; font-weight: 800; margin-bottom: 6px; }
        .header p { font-size: 14px; color: #94a3b8; }
        .card {
            background: rgba(15, 23, 42, 0.88);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 18px;
            padding: 24px;
            margin-bottom: 20px;
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.35);
            color: #fca5a5; padding: 12px 16px; border-radius: 12px; font-size: 13px; margin-bottom: 18px;
        }
        .alert-warn {
            background: rgba(245, 158, 11, 0.12); border: 1px solid rgba(245, 158, 11, 0.35);
            color: #fcd34d; padding: 14px 16px; border-radius: 12px; font-size: 13px; margin-bottom: 18px;
        }
        .pkg-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 12px; margin-bottom: 20px;
        }
        .pkg-item {
            position: relative;
            border: 1.5px solid rgba(255, 255, 255, 0.1);
            border-radius: 14px; padding: 16px; cursor: pointer;
            background: rgba(2, 6, 23, 0.5); transition: all 0.2s;
            display: flex; flex-direction: column; justify-content: space-between; gap: 8px;
        }
        .pkg-item:hover:not(.disabled) { border-color: rgba(0, 212, 255, 0.5); }
        .pkg-item.selected {
            border-color: #25d366; background: rgba(37, 211, 102, 0.09);
            box-shadow: 0 0 0 2px rgba(37, 211, 102, 0.2);
        }
        .pkg-item.disabled { opacity: 0.45; cursor: not-allowed; }
        .pkg-item input[type="radio"] { position: absolute; opacity: 0; pointer-events: none; }
        .pkg-price { font-size: 20px; font-weight: 800; color: #38bdf8; }
        .stock-pill {
            display: inline-block; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 999px;
        }
        .stock-ok { background: rgba(37, 211, 102, 0.15); color: #4ade80; }
        .stock-empty { background: rgba(239, 68, 68, 0.15); color: #f87171; }
        .form-group { margin-bottom: 14px; }
        .form-label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #cbd5e1; }
        .form-control {
            width: 100%; padding: 11px 14px; border-radius: 10px;
            background: rgba(2, 6, 23, 0.7); border: 1px solid rgba(255, 255, 255, 0.12);
            color: #fff; font-size: 14px; outline: none;
        }
        .form-control:focus { border-color: #25d366; }
        .btn-pay {
            width: 100%; padding: 14px; border: none; border-radius: 12px;
            background: linear-gradient(135deg, #25d366, #00d4ff); color: #04101f;
            font-size: 15px; font-weight: 800; cursor: pointer;
        }
        .btn-pay:disabled { opacity: 0.45; cursor: not-allowed; }
        .footer {
            text-align: center; font-size: 13px; color: #94a3b8; margin-top: 12px;
        }
        .footer a { color: #38bdf8; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
<div class="bg-grid"></div>
<div class="container">
    <div class="header">
        <div class="badge-top">⚡ Pembelian Instan Tanpa Login</div>
        <h1>{{ $brandName }}</h1>
        <p>Pilih paket WiFi, bayar via QRIS / Payment Gateway, dan kode voucher langsung aktif di layar Anda.</p>
    </div>

    @if(session('error'))
        <div class="alert-error">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="alert-error">
            @foreach($errors->all() as $err)
                <div>{{ $err }}</div>
            @endforeach
        </div>
    @endif

    @if(!$isGatewayActive)
        <div class="alert-warn">
            <strong>Pembayaran Otomatis Belum Aktif:</strong> Admin WiFi belum mengaktifkan Payment Gateway. Silakan login ke Portal Pelanggan atau hubungi admin/agen terdekat.
        </div>
    @endif

    @if($routers->count() > 1)
    <div class="card" style="padding:16px 20px;">
        <form method="GET" action="{{ route('public.voucher.index') }}" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
            <label for="routerFilter" style="font-size:13px;font-weight:600;color:#cbd5e1;">Lokasi / WiFi (SSID):</label>
            <select id="routerFilter" name="router_id" class="form-control" style="max-width:320px;" onchange="this.form.submit()">
                <option value="">Semua Lokasi WiFi</option>
                @foreach($routers as $r)
                    <option value="{{ $r->id }}" {{ $selectedRouterId == $r->id ? 'selected' : '' }}>
                        {{ $r->ssid ?: $r->name }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>
    @endif

    <div class="card">
        <form action="{{ route('public.voucher.checkout') }}" method="POST" id="checkoutForm">
            @csrf
            <input type="hidden" name="router_id" value="{{ $selectedRouterId }}">

            <div style="font-size:15px;font-weight:700;margin-bottom:12px;">1. Pilih Paket Voucher Hotspot</div>

            @if($packages->isEmpty())
                <div style="color:#94a3b8;font-size:14px;padding:12px 0;">Belum ada paket voucher berbayar yang tersedia saat ini.</div>
            @else
                <div class="pkg-grid">
                    @foreach($packages as $pkg)
                    @php $hasStock = $pkg->available_stock > 0; @endphp
                    <label class="pkg-item {{ !$hasStock ? 'disabled' : '' }} {{ old('package_id') == $pkg->id ? 'selected' : '' }}">
                        <input type="radio" name="package_id" value="{{ $pkg->id }}" {{ !$hasStock ? 'disabled' : '' }}
                               {{ old('package_id') == $pkg->id ? 'checked' : '' }} required onchange="selectCard(this)">
                        <div>
                            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:6px;margin-bottom:6px;">
                                <strong style="font-size:15px;">{{ $pkg->name }}</strong>
                                <span class="stock-pill {{ $hasStock ? 'stock-ok' : 'stock-empty' }}">
                                    {{ $hasStock ? "Stok: {$pkg->available_stock}" : 'Habis' }}
                                </span>
                            </div>
                            <div class="pkg-price">Rp {{ number_format((float)$pkg->price, 0, ',', '.') }}</div>
                        </div>
                        <div style="font-size:12px;color:#94a3b8;">
                            {{ $pkg->duration_days >= 1 ? $pkg->duration_days . ' Hari' : ($pkg->duration_days * 24) . ' Jam' }}
                            &bull; {{ $pkg->bandwidth_up }}/{{ $pkg->bandwidth_down }} Mbps
                        </div>
                    </label>
                    @endforeach
                </div>

                <div style="font-size:15px;font-weight:700;margin-bottom:12px;">2. Data Pembeli</div>

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
                    <div class="form-group">
                        <label class="form-label" for="buyer_name">Nama Pembeli</label>
                        <input type="text" id="buyer_name" name="buyer_name" class="form-control"
                               value="{{ old('buyer_name') }}" placeholder="Contoh: Budi (opsional)">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="buyer_phone">Nomor WhatsApp (Disarankan)</label>
                        <input type="text" id="buyer_phone" name="buyer_phone" class="form-control"
                               value="{{ old('buyer_phone') }}" placeholder="081234567890 (salinan kode dikirim ke WA)">
                    </div>
                </div>

                <button type="submit" class="btn-pay" {{ !$isGatewayActive ? 'disabled' : '' }}>
                    Bayar Sekarang via {{ strtoupper($gatewayConfig['provider'] !== 'manual' ? $gatewayConfig['provider'] : 'Payment Gateway') }} →
                </button>
            @endif
        </form>
    </div>

    <div class="footer">
        Sudah punya akun &amp; saldo deposit? <a href="{{ route('portal.login') }}">Masuk ke Portal Pelanggan</a>
    </div>
</div>

<script>
function selectCard(radio) {
    document.querySelectorAll('.pkg-item').forEach(el => el.classList.remove('selected'));
    if (radio.checked) {
        radio.closest('.pkg-item').classList.add('selected');
    }
}
</script>
</body>
</html>

