<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Pembelian Voucher {{ $order->reference_code }} - {{ $brandName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: #080d1a; color: #e8eaf6;
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            padding: 20px;
        }
        .card {
            width: 100%; max-width: 480px;
            background: rgba(15, 23, 42, 0.9); backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px; padding: 28px 24px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.45);
        }
        .voucher-box {
            background: linear-gradient(135deg, rgba(37, 211, 102, 0.15), rgba(0, 212, 255, 0.08));
            border: 1px solid rgba(37, 211, 102, 0.45);
            border-radius: 16px; padding: 20px; text-align: center; margin: 18px 0;
        }
        .voucher-code {
            font-family: monospace; font-size: 30px; font-weight: 800;
            letter-spacing: 3px; color: #fff; margin: 10px 0;
        }
        .btn {
            display: inline-block; width: 100%; text-align: center; padding: 13px 16px;
            border-radius: 12px; font-size: 14px; font-weight: 700; text-decoration: none;
            cursor: pointer; border: none;
        }
        .btn-primary { background: linear-gradient(135deg, #25d366, #00d4ff); color: #04101f; }
        .btn-outline { background: transparent; border: 1px solid rgba(255, 255, 255, 0.16); color: #cbd5e1; margin-top: 10px; }
        .row { display: flex; justify-content: space-between; font-size: 13px; padding: 6px 0; border-bottom: 1px solid rgba(255,255,255,0.06); }
    </style>
</head>
<body>
<div class="card">
    <div style="text-align:center;margin-bottom:18px;">
        <div style="font-size:12px;color:#38bdf8;font-weight:700;text-transform:uppercase;letter-spacing:0.6px;">{{ $brandName }}</div>
        <h1 style="font-size:20px;font-weight:800;margin-top:4px;">Status Pembelian Voucher</h1>
        <div style="font-family:monospace;font-size:13px;color:#94a3b8;margin-top:4px;">{{ $order->reference_code }}</div>
    </div>

    <div style="margin-bottom:16px;">
        <div class="row">
            <span style="color:#94a3b8;">Paket</span>
            <strong>{{ $order->package?->name }}</strong>
        </div>
        <div class="row">
            <span style="color:#94a3b8;">Pembeli</span>
            <span>{{ $order->buyer_name }}</span>
        </div>
        <div class="row">
            <span style="color:#94a3b8;">Total Bayar</span>
            <strong style="color:#38bdf8;">Rp {{ number_format((float)$order->total_amount, 0, ',', '.') }}</strong>
        </div>
    </div>

    @if($order->status === 'paid' && $order->voucher)
        @php
            $voucher = $order->voucher;
            $router  = $voucher->router ?? $order->router;
            $ssid    = $router?->ssid ?: $router?->name;
        @endphp
        <div class="voucher-box">
            <div style="font-size:12px;color:#4ade80;font-weight:700;">✅ PEMBAYARAN LUNAS — VOUCHER SIAP DIGUNAKAN</div>
            <div class="voucher-code" id="voucherCodeText">{{ $voucher->code }}</div>
            @if($voucher->username && $voucher->username !== $voucher->code)
                <div style="font-size:13px;color:#cbd5e1;">Username: <code>{{ $voucher->username }}</code> &bull; Password: <code>{{ $voucher->password }}</code></div>
            @endif
            @if($ssid)
                <div style="font-size:13px;color:#cbd5e1;margin-top:6px;">Hubungkan ke WiFi: <strong>{{ $ssid }}</strong></div>
            @endif
            <div style="font-size:12px;color:#94a3b8;margin-top:4px;">Berlaku hingga {{ $voucher->expires_at?->format('d/m/Y H:i') }}</div>
        </div>
        <button type="button" class="btn btn-primary" onclick="copyCode('{{ $voucher->code }}', this)">Salin Kode Voucher</button>
        <a href="{{ route('public.voucher.index') }}" class="btn btn-outline">Beli Voucher Lagi</a>

    @elseif($order->status === 'pending')
        <div style="background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.3);border-radius:14px;padding:16px;text-align:center;margin-bottom:16px;">
            <div style="font-size:13px;font-weight:700;color:#fbbf24;margin-bottom:8px;">⏳ Menunggu Pembayaran</div>
            <p style="font-size:12px;color:#cbd5e1;margin-bottom:12px;">
                Selesaikan pembayaran melalui {{ strtoupper($order->gateway) }}. Halaman ini akan memperbarui kode voucher secara otomatis setelah pembayaran terkonfirmasi.
            </p>
            @if($order->qr_url && str_starts_with($order->qr_url, 'http'))
                <div style="background:#fff;padding:10px;border-radius:12px;display:inline-block;margin-bottom:12px;">
                    <img src="{{ $order->qr_url }}" alt="QR Pembayaran" style="width:180px;height:180px;object-fit:contain;" onerror="this.parentElement.style.display='none'">
                </div>
            @endif
            @if($order->payment_url)
                <a href="{{ $order->payment_url }}" target="_blank" rel="noopener" class="btn btn-primary">
                    Buka Halaman Pembayaran →
                </a>
            @endif
        </div>
        <button type="button" class="btn btn-outline" onclick="location.reload()">Cek Status Sekarang</button>
        <a href="{{ route('public.voucher.index') }}" class="btn btn-outline">← Pilih Paket Lain</a>

    @elseif($order->status === 'paid_out_of_stock')
        <div style="background:rgba(245,158,11,0.12);border:1px solid rgba(245,158,11,0.35);border-radius:14px;padding:16px;text-align:center;margin-bottom:16px;">
            <div style="font-size:14px;font-weight:700;color:#fbbf24;margin-bottom:6px;">Pembayaran Diterima, Menunggu Stok Voucher</div>
            <p style="font-size:13px;color:#cbd5e1;">
                Pembayaran Anda sudah lunas, namun stok kode voucher untuk paket ini kebetulan baru saja habis. Admin sudah diberi notifikasi. Simpan kode referensi <code>{{ $order->reference_code }}</code>.
            </p>
        </div>
        <button type="button" class="btn btn-outline" onclick="location.reload()">Refresh Status</button>

    @else
        <div style="background:rgba(239,68,68,0.12);border:1px solid rgba(239,68,68,0.35);border-radius:14px;padding:16px;text-align:center;margin-bottom:16px;">
            <div style="font-size:14px;font-weight:700;color:#f87171;margin-bottom:6px;">Transaksi {{ strtoupper($order->status) }}</div>
            <p style="font-size:13px;color:#cbd5e1;">Transaksi ini telah kedaluwarsa atau gagal dibayar.</p>
        </div>
        <a href="{{ route('public.voucher.index') }}" class="btn btn-primary">Buat Pembelian Baru</a>
    @endif
</div>

<script>
function copyCode(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        const orig = btn.innerText;
        btn.innerText = 'Kode Tersalin!';
        setTimeout(() => btn.innerText = orig, 2000);
    });
}

@if($order->status === 'pending')
setInterval(() => {
    fetch('{{ route('public.voucher.poll', $order->reference_code) }}', { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            if (data.status && data.status !== 'pending') {
                location.reload();
            }
        })
        .catch(() => {});
}, 4000);
@endif
</script>
</body>
</html>

