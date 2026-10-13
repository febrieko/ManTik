@extends('layouts.app')

@section('title', 'WA Voucher Gateway')
@section('page-title', 'WhatsApp Voucher Gateway')

@section('content')
<div class="page-header">
    <div class="page-header-title">
        <h2 style="display:flex;align-items:center;gap:10px;">
            <span style="display:inline-flex;width:36px;height:36px;background:linear-gradient(135deg,#25d366,#128C7E);border-radius:10px;align-items:center;justify-content:center;">
                <svg viewBox="0 0 24 24" fill="white" width="20" height="20"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            </span>
            Riwayat Request Voucher via WhatsApp
        </h2>
        <p>Monitor semua permintaan voucher hotspot yang masuk melalui WhatsApp Gateway</p>
    </div>
    <div class="d-flex gap-8">
        <a href="{{ route('whatsapp.settings') }}" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                <circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
            </svg>
            Pengaturan Gateway
        </a>
    </div>
</div>

{{-- Statistik Cards --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
    <div class="card" style="padding:20px;border-left:3px solid #00d4ff;">
        <div style="font-size:13px;color:var(--text-muted);margin-bottom:6px;">Total Request</div>
        <div style="font-size:28px;font-weight:700;color:var(--text-primary);">{{ number_format($stats['total']) }}</div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">Semua waktu</div>
    </div>
    <div class="card" style="padding:20px;border-left:3px solid #25d366;">
        <div style="font-size:13px;color:var(--text-muted);margin-bottom:6px;">Berhasil</div>
        <div style="font-size:28px;font-weight:700;color:#25d366;">{{ number_format($stats['success']) }}</div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">Voucher terkirim</div>
    </div>
    <div class="card" style="padding:20px;border-left:3px solid #ef4444;">
        <div style="font-size:13px;color:var(--text-muted);margin-bottom:6px;">Gagal</div>
        <div style="font-size:28px;font-weight:700;color:#ef4444;">{{ number_format($stats['failed']) }}</div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">Request ditolak</div>
    </div>
    <div class="card" style="padding:20px;border-left:3px solid #f59e0b;">
        <div style="font-size:13px;color:var(--text-muted);margin-bottom:6px;">Hari Ini</div>
        <div style="font-size:28px;font-weight:700;color:#f59e0b;">{{ number_format($stats['today']) }}</div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">{{ now()->format('d M Y') }}</div>
    </div>
</div>

{{-- Filter --}}
<div class="card mb-24">
    <form action="{{ route('whatsapp.voucher-requests') }}" method="GET" class="d-flex gap-12 align-center flex-wrap">
        <div style="flex:1;min-width:200px;">
            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari nomor WA / nama / kode voucher...">
        </div>
        <div style="min-width:150px;">
            <select name="status" class="form-control" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>✅ Berhasil</option>
                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>❌ Gagal</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>⏳ Pending</option>
            </select>
        </div>
        <div style="min-width:160px;">
            <select name="package_id" class="form-control" onchange="this.form.submit()">
                <option value="">Semua Paket</option>
                @foreach($packages as $pkg)
                <option value="{{ $pkg->id }}" {{ request('package_id') == $pkg->id ? 'selected' : '' }}>{{ $pkg->name }}</option>
                @endforeach
            </select>
        </div>
        <div style="min-width:130px;">
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control" placeholder="Dari tanggal">
        </div>
        <div style="min-width:130px;">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control" placeholder="Sampai tanggal">
        </div>
        <button type="submit" class="btn btn-primary">Cari</button>
        @if(request()->hasAny(['search','status','package_id','date_from','date_to']))
        <a href="{{ route('whatsapp.voucher-requests') }}" class="btn btn-secondary">Reset</a>
        @endif
    </form>
</div>

{{-- Tabel Riwayat --}}
<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Nomor WhatsApp</th>
                    <th>Nama</th>
                    <th>Paket Diminta</th>
                    <th>Kode Voucher</th>
                    <th>Status</th>
                    <th>Keterangan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $req)
                <tr>
                    <td style="white-space:nowrap;font-size:12px;color:var(--text-muted);">
                        {{ $req->created_at->format('d/m/Y') }}<br>
                        <strong>{{ $req->created_at->format('H:i:s') }}</strong>
                    </td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span style="width:32px;height:32px;background:linear-gradient(135deg,#25d366,#128C7E);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <svg viewBox="0 0 24 24" fill="white" width="14" height="14"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            </span>
                            <div>
                                <div style="font-weight:600;font-size:13px;">{{ $req->formatted_phone }}</div>
                                <div style="font-size:11px;color:var(--text-muted);">{{ $req->phone }}</div>
                            </div>
                        </div>
                    </td>
                    <td>{{ $req->requester_name ?? '-' }}</td>
                    <td>
                        @if($req->package)
                            <span class="badge" style="background:rgba(0,212,255,0.1);color:#00d4ff;border:1px solid rgba(0,212,255,0.2);">{{ $req->package->name }}</span>
                        @else
                            <span style="color:var(--text-muted);font-size:12px;">{{ $req->package_code ?? '-' }}</span>
                        @endif
                    </td>
                    <td>
                        @if($req->voucher_code)
                            <code style="background:rgba(255,255,255,0.05);padding:3px 8px;border-radius:4px;font-size:13px;letter-spacing:1px;font-weight:600;">{{ $req->voucher_code }}</code>
                        @else
                            <span style="color:var(--text-muted);">-</span>
                        @endif
                    </td>
                    <td>
                        @if($req->status === 'success')
                            <span class="badge badge-success">✓ Berhasil</span>
                        @elseif($req->status === 'failed')
                            <span class="badge badge-danger">✗ Gagal</span>
                        @else
                            <span class="badge badge-warning">⏳ Pending</span>
                        @endif
                    </td>
                    <td style="max-width:200px;">
                        @if($req->failure_reason)
                            <span style="font-size:11px;color:#ef4444;" title="{{ $req->failure_reason }}">{{ Str::limit($req->failure_reason, 50) }}</span>
                        @elseif($req->voucher)
                            <span style="font-size:11px;color:var(--text-muted);">
                                Berlaku hingga: {{ $req->voucher->expires_at ? $req->voucher->expires_at->format('d/m/Y H:i') : '-' }}
                            </span>
                        @else
                            <span style="color:var(--text-muted);">-</span>
                        @endif
                    </td>
                    <td>
                        @if($req->status === 'success' && $req->voucher)
                        <form action="{{ route('whatsapp.voucher-requests.resend', $req) }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-secondary" title="Kirim Ulang Voucher"
                                onclick="return confirm('Kirim ulang voucher ke {{ $req->formatted_phone }}?')">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                                    <polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/>
                                </svg>
                                Kirim Ulang
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center;padding:48px;color:var(--text-muted);">
                        <div style="display:flex;flex-direction:column;align-items:center;gap:12px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="48" height="48" style="opacity:0.3;">
                                <path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/>
                            </svg>
                            <div>Belum ada request voucher via WhatsApp</div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($requests->hasPages())
    <div style="padding:16px 20px;border-top:1px solid var(--border-color);">
        {{ $requests->links() }}
    </div>
    @endif
</div>

@endsection
