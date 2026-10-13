@extends('layouts.app')

@section('title', 'Riwayat Voucher Telegram')
@section('page-title', 'Riwayat Voucher Telegram')

@section('content')
<div class="page-header">
    <div class="page-header-title">
        <h2 style="display:flex;align-items:center;gap:10px;">
            <span style="display:inline-flex;width:36px;height:36px;background:linear-gradient(135deg,#0088cc,#00b0f0);border-radius:10px;align-items:center;justify-content:center;">
                <svg viewBox="0 0 24 24" fill="white" width="20" height="20">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/>
                </svg>
            </span>
            Riwayat Pembelian via Bot
        </h2>
        <p>Monitor riwayat pengunjung yang membeli voucher melalui Telegram Bot.</p>
    </div>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Pengguna</th>
                    <th>Paket Dibeli</th>
                    <th>Kode Voucher</th>
                    <th>Saldo Dipotong</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $req)
                <tr>
                    <td style="font-size:12px;color:var(--text-muted);">
                        {{ $req->created_at->format('d/m/Y') }}<br>
                        <strong>{{ $req->created_at->format('H:i:s') }}</strong>
                    </td>
                    <td>
                        @if($req->telegramUser)
                            <div style="font-weight:600;">{{ $req->telegramUser->full_name }}</div>
                            @if($req->telegramUser->username)
                                <div style="font-size:11px;color:#00d4ff;">@{{ $req->telegramUser->username }}</div>
                            @endif
                        @else
                            <span style="color:var(--text-muted);">-</span>
                        @endif
                    </td>
                    <td>
                        @if($req->package)
                            <span class="badge" style="background:rgba(0,212,255,0.1);color:#00d4ff;border:1px solid rgba(0,212,255,0.2);">{{ $req->package->name }}</span>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if($req->voucher)
                            <code style="background:rgba(255,255,255,0.05);padding:3px 8px;border-radius:4px;font-size:13px;letter-spacing:1px;font-weight:600;">{{ $req->voucher->code }}</code>
                        @else
                            -
                        @endif
                    </td>
                    <td style="color:#ef4444;font-weight:bold;">
                        - Rp {{ number_format($req->price_deducted, 0, ',', '.') }}
                    </td>
                    <td>
                        @if($req->status === 'success')
                            <span class="badge badge-success">✓ Berhasil</span>
                        @else
                            <span class="badge badge-danger">✗ Gagal</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center;padding:48px;color:var(--text-muted);">
                        Belum ada riwayat pembelian voucher via Telegram.
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
