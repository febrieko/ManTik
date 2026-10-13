@extends('layouts.app')

@section('title', 'Segera Hadir')
@section('page-title', $module ?? 'Modul')

@section('content')
<div class="card" style="text-align: center; padding: 80px 40px;">
    <div style="width: 80px; height: 80px; margin: 0 auto 24px; background: linear-gradient(135deg, rgba(0,212,255,0.15), rgba(0,102,255,0.1)); border-radius: 20px; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(0,212,255,0.2);">
        <svg viewBox="0 0 24 24" fill="none" stroke="#00d4ff" stroke-width="1.5" style="width: 40px; height: 40px;">
            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
        </svg>
    </div>
    <h2 style="font-size: 24px; font-weight: 800; margin-bottom: 12px; background: linear-gradient(135deg, #00d4ff, #0066ff); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
        Segera Hadir
    </h2>
    <p style="color: #8892b0; font-size: 15px; max-width: 400px; margin: 0 auto 24px;">
        Modul <strong style="color: #e8eaf6;">{{ $module ?? 'ini' }}</strong> sedang dalam pengembangan dan akan segera tersedia.
    </p>
    <a href="{{ route('dashboard') }}" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Kembali ke Dashboard
    </a>
</div>
@endsection
