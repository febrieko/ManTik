<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Voucher Hotspot</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background: #f0f2f5; margin: 0; padding: 20px; }
        .print-container { max-width: 800px; margin: 0 auto; display: flex; flex-wrap: wrap; gap: 15px; justify-content: center; }
        .voucher {
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 8px;
            width: 220px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
            page-break-inside: avoid;
        }
        .v-header { text-align: center; border-bottom: 2px dashed #eee; padding-bottom: 10px; margin-bottom: 10px; }
        .v-title { font-size: 16px; font-weight: bold; color: #333; margin: 0; }
        .v-subtitle { font-size: 11px; color: #666; margin-top: 2px; }
        .v-body { text-align: center; }
        .v-package { font-size: 12px; font-weight: bold; color: #0066ff; margin-bottom: 10px; background: #e6f0ff; display: inline-block; padding: 3px 8px; border-radius: 4px; }
        .v-cred { margin-bottom: 8px; }
        .v-label { font-size: 10px; color: #888; text-transform: uppercase; }
        .v-value { font-size: 18px; font-weight: bold; font-family: monospace; color: #111; letter-spacing: 1px; }
        .v-footer { border-top: 1px solid #eee; padding-top: 8px; margin-top: 10px; text-align: center; font-size: 9px; color: #999; }
        
        .no-print { text-align: center; margin-bottom: 20px; }
        .btn { background: #0066ff; color: #fff; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 14px; font-weight: bold; }
        
        @media print {
            body { background: #fff; padding: 0; }
            .no-print { display: none; }
            .voucher { border: 1px solid #000; box-shadow: none; }
            .v-package { border: 1px solid #000; background: transparent; color: #000; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="btn" onclick="window.print()">🖨 Cetak Sekarang</button>
        <button class="btn" onclick="window.close()" style="background: #6c757d; margin-left: 10px;">Tutup</button>
    </div>

    <div class="print-container">
        @foreach($vouchers as $v)
        <div class="voucher">
            <div class="v-header">
                <h3 class="v-title">{{ config('app.name', 'MANTIK Hotspot') }}</h3>
                <div class="v-subtitle">Voucher Akses Internet</div>
            </div>
            <div class="v-body">
                <div class="v-package">{{ $v->package->name ?? $v->profile }}</div>
                
                @if($v->username === $v->password)
                    <div class="v-cred">
                        <div class="v-label">KODE VOUCHER</div>
                        <div class="v-value">{{ $v->username }}</div>
                    </div>
                @else
                    <div class="v-cred">
                        <div class="v-label">USERNAME</div>
                        <div class="v-value">{{ $v->username }}</div>
                    </div>
                    <div class="v-cred">
                        <div class="v-label">PASSWORD</div>
                        <div class="v-value">{{ $v->password }}</div>
                    </div>
                @endif
            </div>
            <div class="v-footer">
                Login ke jaringan WiFi kami dan masukkan kode di atas.<br>
                Berlaku sesuai paket yang dipilih.
            </div>
        </div>
        @endforeach
    </div>
</body>
</html>
