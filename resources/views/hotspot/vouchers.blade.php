@extends('layouts.app')

@section('title', 'Hotspot Vouchers')
@section('page-title', 'Hotspot Vouchers')

@section('content')
<div class="page-header">
    <div class="page-header-title">
        <h2>Manajemen Voucher Hotspot</h2>
        <p>Kelola dan cetak voucher untuk pengguna WiFi Hotspot</p>
    </div>
    <div class="d-flex gap-8">
        <a href="{{ route('hotspot.vouchers.generate') }}" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
            Generate Voucher
        </a>
    </div>
</div>

<div class="card mb-24">
    <form action="{{ route('hotspot.vouchers') }}" method="GET" class="d-flex gap-12 align-center">
        <div style="flex: 1; max-width: 300px;">
            <select name="router_id" class="form-control" onchange="this.form.submit()">
                <option value="">Semua Router</option>
                @foreach($routers as $r)
                <option value="{{ $r->id }}" {{ $routerId == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                @endforeach
            </select>
        </div>
    </form>
</div>

<div class="card">
    <form action="{{ route('hotspot.vouchers.print-bulk') }}" method="POST" target="_blank" id="bulkForm">
        @csrf
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th width="40"><input type="checkbox" id="selectAll"></th>
                        <th>Kode Voucher</th>
                        <th>Username</th>
                        <th>Password</th>
                        <th>Router</th>
                        <th>Paket / Profile</th>
                        <th>Status</th>
                        <th>Dibuat</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vouchers as $voucher)
                    <tr>
                        <td><input type="checkbox" name="vouchers[]" value="{{ $voucher->id }}" class="voucher-cb"></td>
                        <td>
                            <div class="font-mono font-bold text-lg" style="color: #00d4ff;">{{ $voucher->code }}</div>
                        </td>
                        <td class="font-mono text-sm">{{ $voucher->username }}</td>
                        <td class="font-mono text-sm">{{ $voucher->password }}</td>
                        <td>{{ $voucher->router->name ?? '-' }}</td>
                        <td>
                            <span class="badge badge-info">{{ $voucher->profile }}</span>
                            @if($voucher->package)
                            <div class="text-sm text-muted mt-1">{{ $voucher->package->name }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $voucher->is_used ? 'badge-danger' : 'badge-success' }}">
                                {{ $voucher->is_used ? 'Terpakai' : 'Tersedia' }}
                            </span>
                        </td>
                        <td class="text-sm text-muted">{{ $voucher->created_at->format('d M Y H:i') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center" style="padding: 40px;">
                            <div class="empty-state">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M1.42 9A16 16 0 0122.58 9M5 12.55a11 11 0 0114.08 0"/></svg>
                                <h3>Belum ada voucher</h3>
                                <p>Generate voucher pertama Anda sekarang</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($vouchers->count() > 0)
        <div class="d-flex justify-between align-center mt-16" style="padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.05);">
            <div>
                <button type="submit" class="btn btn-outline" id="printBtn" disabled>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    Cetak Terpilih (<span id="selCount">0</span>)
                </button>
            </div>
            
            @if($vouchers->hasPages())
            <div>{{ $vouchers->appends(request()->query())->links('pagination::bootstrap-4') }}</div>
            @endif
        </div>
        @endif
    </form>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('selectAll')?.addEventListener('change', function(e) {
    document.querySelectorAll('.voucher-cb').forEach(cb => cb.checked = e.target.checked);
    updatePrintBtn();
});

document.querySelectorAll('.voucher-cb').forEach(cb => {
    cb.addEventListener('change', updatePrintBtn);
});

function updatePrintBtn() {
    const checked = document.querySelectorAll('.voucher-cb:checked').length;
    const btn = document.getElementById('printBtn');
    if (btn) {
        document.getElementById('selCount').textContent = checked;
        btn.disabled = checked === 0;
    }
}
</script>
@endpush
