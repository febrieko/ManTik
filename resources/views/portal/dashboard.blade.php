@extends('portal.layout')

@section('title', 'Dashboard Pelanggan')

@section('content')
@if(session('success'))
<div style="background:rgba(37,211,102,0.12);border:1px solid rgba(37,211,102,0.35);color:#4ade80;padding:14px 16px;border-radius:12px;margin-bottom:20px;font-size:13px;">
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div style="background:rgba(239,68,68,0.12);border:1px solid rgba(239,68,68,0.35);color:#f87171;padding:14px 16px;border-radius:12px;margin-bottom:20px;font-size:13px;">
    {{ session('error') }}
</div>
@endif

@if($errors->any())
<div style="background:rgba(239,68,68,0.12);border:1px solid rgba(239,68,68,0.35);color:#f87171;padding:14px 16px;border-radius:12px;margin-bottom:20px;font-size:13px;">
    @foreach($errors->all() as $err)
        <div>{{ $err }}</div>
    @endforeach
</div>
@endif

@if(session('payment_url'))
<div class="card" style="padding:18px 22px;margin-bottom:20px;border:1px solid rgba(0,212,255,0.45);background:linear-gradient(135deg,rgba(0,212,255,0.12),rgba(168,85,247,0.06));display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;">
    <div>
        <div style="font-size:12px;color:#00d4ff;font-weight:700;text-transform:uppercase;">Pembayaran Otomatis Siap</div>
        <div style="font-size:14px;margin-top:2px;">Klik tombol di samping untuk membuka halaman pembayaran ({{ strtoupper($gatewayConfig['provider'] ?? 'Gateway') }}). Saldo otomatis bertambah setelah terbayar.</div>
    </div>
    <a href="{{ session('payment_url') }}" target="_blank" rel="noopener" class="btn btn-cyan">Bayar Sekarang →</a>
</div>
@endif

@if(session('purchased_voucher'))
@php $pv = session('purchased_voucher'); @endphp
<div class="card" style="padding:22px;margin-bottom:24px;border:1px solid rgba(37,211,102,0.45);background:linear-gradient(135deg,rgba(37,211,102,0.12),rgba(0,212,255,0.06));">
    <div style="font-size:12px;color:#4ade80;font-weight:700;text-transform:uppercase;letter-spacing:0.6px;margin-bottom:6px;">
        Voucher Hotspot Siap Digunakan
    </div>
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;">
        <div>
            <div style="font-size:28px;font-weight:800;letter-spacing:2px;color:#fff;" id="new-voucher-code">{{ $pv['code'] }}</div>
            <div style="font-size:13px;color:var(--text-muted);margin-top:4px;">
                Paket: <strong>{{ $pv['package_name'] }}</strong>
                @if(!empty($pv['ssid'])) &bull; WiFi: <strong>{{ $pv['ssid'] }}</strong> @endif
                &bull; Berlaku s/d {{ $pv['expires_at'] }}
            </div>
        </div>
        <button type="button" class="btn btn-primary" onclick="copyText('{{ $pv['code'] }}', this)">Salin Kode Voucher</button>
    </div>
</div>
@endif

{{-- Grid Ringkasan Atas --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin-bottom:24px;">
    {{-- Kartu Saldo --}}
    <div class="card" style="padding:24px;border-left:4px solid #25d366;">
        <div style="font-size:12px;color:var(--text-muted);margin-bottom:4px;">Saldo Deposit Anda</div>
        <div style="font-size:32px;font-weight:800;color:#4ade80;margin-bottom:12px;">
            Rp {{ number_format((float)$customer->balance, 0, ',', '.') }}
        </div>
        <div style="font-size:12px;color:var(--text-muted);">
            Tipe Akun: <strong style="color:#fff;">{{ $customer->customer_type === 'reseller' ? 'Reseller / Agen' : 'Pelanggan Regular' }}</strong><br>
            No. WA Terdaftar: <strong style="color:#fff;">{{ $customer->phone }}</strong>
        </div>
    </div>

    {{-- Kartu Layanan Aktif & Tagihan --}}
    <div class="card" style="padding:24px;border-left:4px solid #00d4ff;">
        <div style="font-size:12px;color:var(--text-muted);margin-bottom:8px;">Layanan &amp; Tagihan</div>
        <div style="display:grid;gap:8px;font-size:13px;">
            <div style="display:flex;justify-content:space-between;">
                <span style="color:var(--text-muted);">Layanan PPPoE</span>
                <strong>{{ $customer->pppoeUsers->count() }} Akun</strong>
            </div>
            <div style="display:flex;justify-content:space-between;">
                <span style="color:var(--text-muted);">Layanan Hotspot</span>
                <strong>{{ $customer->hotspotUsers->count() }} Akun</strong>
            </div>
            <div style="display:flex;justify-content:space-between;">
                <span style="color:var(--text-muted);">Tagihan Belum Lunas</span>
                @php $unpaidCount = $billings->whereIn('status', ['unpaid','partial','overdue'])->count(); @endphp
                <strong style="color:{{ $unpaidCount > 0 ? '#fbbf24' : '#4ade80' }};">{{ $unpaidCount }} Invoice</strong>
            </div>
        </div>
    </div>

    {{-- Kartu Ajukan Top-Up --}}
    <div class="card" style="padding:24px;">
        <div style="font-size:14px;font-weight:700;margin-bottom:10px;">Ajukan Top-Up Saldo</div>
        <form action="{{ route('portal.topup.request') }}" method="POST" style="display:grid;gap:10px;">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                <input type="number" name="amount" min="5000" step="1000" class="form-control" placeholder="Nominal (min 5.000)" required>
                <select name="payment_method" class="form-control">
                    @if(!empty($isGatewayActive))
                        <option value="payment_gateway" selected>Pembayaran Otomatis ({{ strtoupper($gatewayConfig['provider'] ?? 'Gateway') }})</option>
                    @endif
                    <option value="bank_transfer">Transfer Bank (Konfirmasi Admin)</option>
                    <option value="admin_cash">Bayar Tunai ke Admin</option>
                </select>
            </div>
            <input type="text" name="notes" class="form-control" placeholder="Catatan transfer (opsional)">
            <button type="submit" class="btn btn-cyan">Buat Pengajuan Top-Up</button>
        </form>
    </div>
</div>

{{-- Seksi Beli Voucher Hotspot --}}
<div class="card" style="padding:24px;margin-bottom:24px;">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:16px;">
        <div>
            <h2 style="font-size:17px;font-weight:700;">Beli Voucher Hotspot (Potong Saldo)</h2>
            <p style="font-size:12px;color:var(--text-muted);">Kode voucher langsung aktif dan dikirimkan ke akun &amp; WhatsApp Anda</p>
        </div>
    </div>

    @if($availablePackages->isEmpty())
        <div style="color:var(--text-muted);font-size:13px;">Belum ada paket hotspot berbayar yang tersedia saat ini.</div>
    @else
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px;">
            @foreach($availablePackages as $pkg)
            <div style="border:1px solid var(--border);border-radius:14px;padding:16px;background:rgba(2,6,23,0.45);display:flex;flex-direction:column;justify-content:space-between;gap:12px;">
                <div>
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
                        <strong style="font-size:15px;">{{ $pkg->name }}</strong>
                        <span class="badge {{ $pkg->available_stock > 0 ? 'badge-success' : 'badge-danger' }}">
                            Stok: {{ $pkg->available_stock }}
                        </span>
                    </div>
                    <div style="font-size:20px;font-weight:800;color:#00d4ff;margin:8px 0 4px;">
                        Rp {{ number_format((float)$pkg->price, 0, ',', '.') }}
                    </div>
                    <div style="font-size:12px;color:var(--text-muted);">
                        Durasi: {{ $pkg->duration_days >= 1 ? $pkg->duration_days . ' Hari' : ($pkg->duration_days * 24) . ' Jam' }}
                        &bull; {{ $pkg->bandwidth_up }}/{{ $pkg->bandwidth_down }} Mbps
                    </div>
                </div>

                @if($pkg->available_stock > 0 && (float)$customer->balance >= (float)$pkg->price)
                <form action="{{ route('portal.vouchers.buy') }}" method="POST" style="display:grid;gap:8px;" onsubmit="return confirm('Beli voucher {{ $pkg->name }} seharga Rp {{ number_format((float)$pkg->price, 0, ',', '.') }}?')">
                    @csrf
                    <input type="hidden" name="package_id" value="{{ $pkg->id }}">
                    @if($routers->count() > 1)
                    <select name="router_id" class="form-control" style="padding:7px 10px;font-size:12px;">
                        <option value="">Semua Lokasi WiFi</option>
                        @foreach($routers as $r)
                            <option value="{{ $r->id }}">{{ $r->ssid ?: $r->name }}</option>
                        @endforeach
                    </select>
                    @endif
                    <button type="submit" class="btn btn-primary" style="width:100%;">Beli Sekarang</button>
                </form>
                @elseif($pkg->available_stock <= 0)
                    <button type="button" class="btn btn-outline" disabled style="width:100%;opacity:0.5;cursor:not-allowed;">Stok Habis</button>
                @else
                    <button type="button" class="btn btn-outline" disabled style="width:100%;opacity:0.5;cursor:not-allowed;">Saldo Tidak Cukup</button>
                @endif
            </div>
            @endforeach
        </div>
    @endif
</div>

{{-- Seksi Tagihan Bulanan Anda --}}
@if($billings->isNotEmpty())
<div class="card" style="margin-bottom:24px;">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
        <div>
            <h2 style="font-size:16px;font-weight:700;">Tagihan Layanan Bulanan Anda</h2>
            <p style="font-size:12px;color:var(--text-muted);">Lunasi tagihan bulanan secara instan menggunakan saldo deposit Anda</p>
        </div>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>No. Invoice</th>
                    <th>Paket &amp; Periode</th>
                    <th>Jatuh Tempo</th>
                    <th>Sisa / Total</th>
                    <th>Status</th>
                    <th style="text-align:right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($billings as $bill)
                <tr>
                    <td><code style="font-weight:700;color:#00d4ff;">{{ $bill->invoice_number }}</code></td>
                    <td style="font-size:12px;">
                        <strong>{{ $bill->package?->name ?? 'Tagihan Internet' }}</strong><br>
                        <span style="color:var(--text-muted);">{{ $bill->period_start->format('d M') }} – {{ $bill->period_end->format('d M Y') }}</span>
                    </td>
                    <td style="font-size:12px;color:{{ $bill->isOverdue() ? '#f87171' : 'var(--text-muted)' }};">
                        {{ $bill->due_date->format('d M Y') }}
                    </td>
                    <td style="font-weight:700;">
                        Rp {{ number_format($bill->status === 'paid' ? (float)$bill->amount : $bill->remaining_amount, 0, ',', '.') }}
                    </td>
                    <td>
                        @if($bill->status === 'paid')
                            <span class="badge badge-success">Lunas</span>
                        @elseif($bill->status === 'overdue')
                            <span class="badge badge-danger">Overdue</span>
                        @elseif($bill->status === 'partial')
                            <span class="badge badge-warning">Cicilan</span>
                        @elseif($bill->status === 'cancelled')
                            <span class="badge" style="opacity:0.6;">Batal</span>
                        @else
                            <span class="badge badge-warning">Belum Bayar</span>
                        @endif
                    </td>
                    <td style="text-align:right;">
                        @if(in_array($bill->status, ['unpaid', 'partial', 'overdue']) && $bill->remaining_amount > 0)
                            @if((float)$customer->balance >= $bill->remaining_amount)
                                <form action="{{ route('portal.billing.pay-balance', $bill) }}" method="POST" style="display:inline;"
                                      onsubmit="return confirm('Bayar tagihan {{ $bill->invoice_number }} sebesar Rp {{ number_format($bill->remaining_amount, 0, ',', '.') }} memakai saldo deposit?')">
                                    @csrf
                                    <button type="submit" class="btn btn-primary" style="padding:6px 12px;font-size:12px;">Bayar Pakai Saldo</button>
                                </form>
                            @else
                                <span style="font-size:11px;color:#fbbf24;">Saldo kurang (Top-up dulu)</span>
                            @endif
                        @elseif($bill->status === 'paid')
                            <span style="font-size:12px;color:#4ade80;">Terbayar ({{ $bill->paid_at?->format('d/m/Y') }})</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Grid Riwayat Voucher & Mutasi Saldo --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:20px;margin-bottom:24px;">
    {{-- Riwayat Voucher --}}
    <div class="card">
        <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
            <h3 style="font-size:15px;">Riwayat Voucher Anda</h3>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Paket</th>
                        <th>Kode Voucher</th>
                        <th>Harga</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($voucherHistory as $item)
                    <tr>
                        <td style="font-size:12px;color:var(--text-muted);">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                        <td style="font-size:12px;">{{ $item->package?->name ?: $item->package_code }}</td>
                        <td>
                            <code style="background:rgba(255,255,255,0.08);padding:3px 8px;border-radius:6px;font-weight:700;">{{ $item->voucher_code }}</code>
                        </td>
                        <td style="font-weight:600;">Rp {{ number_format((float)$item->price_deducted, 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="text-align:center;padding:24px;color:var(--text-muted);">Belum ada riwayat pembelian voucher.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Riwayat Mutasi Saldo --}}
    <div class="card">
        <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
            <h3 style="font-size:15px;">Mutasi Saldo Terakhir</h3>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Transaksi</th>
                        <th>Nominal</th>
                        <th>Saldo Akhir</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($balanceTransactions as $trx)
                    <tr>
                        <td style="font-size:12px;color:var(--text-muted);">{{ $trx->created_at->format('d/m H:i') }}</td>
                        <td style="font-size:12px;">
                            <strong>{{ $trx->category_label }}</strong><br>
                            <span style="color:var(--text-muted);font-size:11px;">{{ Str::limit($trx->notes, 35) }}</span>
                        </td>
                        <td style="font-weight:700;color:{{ $trx->type === 'credit' ? '#4ade80' : '#f87171' }};white-space:nowrap;">
                            {{ $trx->type === 'credit' ? '+' : '-' }} Rp {{ number_format((float)$trx->amount, 0, ',', '.') }}
                        </td>
                        <td style="font-weight:600;">Rp {{ number_format((float)$trx->balance_after, 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="text-align:center;padding:24px;color:var(--text-muted);">Belum ada mutasi saldo.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Grid Tagihan & Ganti Password --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:20px;">
    {{-- Riwayat Pengajuan Top-up --}}
    <div class="card">
        <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
            <h3 style="font-size:15px;">Status Pengajuan Top-Up</h3>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Kode Ref</th>
                        <th>Nominal</th>
                        <th>Metode</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topupTransactions as $topup)
                    <tr>
                        <td style="font-size:12px;"><code>{{ $topup->reference_code }}</code></td>
                        <td style="font-weight:600;">Rp {{ number_format((float)$topup->amount, 0, ',', '.') }}</td>
                        <td style="font-size:12px;">{{ $topup->payment_method_label }}</td>
                        <td>
                            @if($topup->status === 'paid')
                                <span class="badge badge-success">Lunas</span>
                            @elseif($topup->status === 'pending')
                                <span class="badge badge-warning">Menunggu</span>
                                @if(!empty($topup->payment_url))
                                    <div style="margin-top:4px;">
                                        <a href="{{ $topup->payment_url }}" target="_blank" rel="noopener" style="font-size:11px;color:#00d4ff;font-weight:700;text-decoration:underline;">Bayar Sekarang →</a>
                                    </div>
                                @endif
                            @else
                                <span class="badge badge-danger">{{ ucfirst($topup->status) }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="text-align:center;padding:24px;color:var(--text-muted);">Belum ada riwayat top-up.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Ubah Password Portal --}}
    <div class="card" style="padding:20px;">
        <h3 style="font-size:15px;margin-bottom:12px;">Keamanan Akun (Ubah Password)</h3>
        <form action="{{ route('portal.password.update') }}" method="POST" style="display:grid;gap:12px;">
            @csrf
            <div>
                <label style="display:block;font-size:12px;color:var(--text-muted);margin-bottom:4px;">Password Saat Ini</label>
                <input type="password" name="current_password" class="form-control" required>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <div>
                    <label style="display:block;font-size:12px;color:var(--text-muted);margin-bottom:4px;">Password Baru</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div>
                    <label style="display:block;font-size:12px;color:var(--text-muted);margin-bottom:4px;">Konfirmasi Password</label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>
            </div>
            <div style="text-align:right;">
                <button type="submit" class="btn btn-outline">Perbarui Password</button>
            </div>
        </form>
    </div>
</div>

<script>
function copyText(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        const original = btn.innerText;
        btn.innerText = 'Tersalin!';
        setTimeout(() => btn.innerText = original, 2000);
    });
}
</script>
@endsection

