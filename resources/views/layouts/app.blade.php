<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MANTIK') - Manajemen MikroTik</title>
    <meta name="description" content="MANTIK - Sistem Manajemen MikroTik terpadu untuk mengelola router, PPPoE, Hotspot, dan billing pelanggan.">

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    {{-- Custom CSS --}}
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pagination.css') }}">

    @stack('styles')
</head>
<body>
    {{-- Sidebar --}}
    <aside class="sidebar" id="sidebar">
        {{-- Logo --}}
        <div class="sidebar-logo">
            <div class="logo-icon">
                <svg viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M16 2L4 8v16l12 6 12-6V8L16 2z" fill="url(#logoGrad)" opacity="0.9"/>
                    <path d="M16 2v28M4 8l12 6 12-6" stroke="rgba(255,255,255,0.3)" stroke-width="0.5"/>
                    <circle cx="16" cy="14" r="4" fill="rgba(255,255,255,0.9)"/>
                    <defs>
                        <linearGradient id="logoGrad" x1="4" y1="2" x2="28" y2="30" gradientUnits="userSpaceOnUse">
                            <stop offset="0%" stop-color="#00d4ff"/>
                            <stop offset="100%" stop-color="#0066ff"/>
                        </linearGradient>
                    </defs>
                </svg>
            </div>
            <div class="logo-text">
                <span class="logo-title">MANTIK</span>
                <span class="logo-sub">MikroTik Manager</span>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="sidebar-nav">
            <div class="nav-section">
                <span class="nav-section-label">UTAMA</span>
                <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                            <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
                        </svg>
                    </span>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('routers.index') }}" class="nav-item {{ request()->routeIs('routers.*') ? 'active' : '' }}">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12H3a9 9 0 0018 0h-2M12 3v2m0 14v2M4.22 4.22l1.42 1.42m12.72 12.72l1.42 1.42M4.22 19.78l1.42-1.42m12.72-12.72l1.42-1.42"/>
                        </svg>
                    </span>
                    <span>Router</span>
                    @if(isset($onlineRouters)) <span class="nav-badge success">{{ $onlineRouters }}</span> @endif
                </a>
                @if(auth()->check() && (auth()->user()->isSuperAdmin() || auth()->user()->isAdmin() || auth()->user()->isTeknisi()))
                <a href="{{ route('router-status.index') }}" class="nav-item {{ request()->routeIs('router-status.*') ? 'active' : '' }}">
                    <span class="nav-icon" style="color: #00d4ff;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                        </svg>
                    </span>
                    <span>Status & Uptime</span>
                    <span class="nav-badge" style="background:rgba(0,212,255,0.15);color:#00d4ff;border:1px solid rgba(0,212,255,0.3);">LIVE</span>
                </a>
                @endif
            </div>

            <div class="nav-section">
                <span class="nav-section-label">MANAJEMEN</span>
                @can('view pppoe')
                <a href="{{ route('pppoe.index') }}" class="nav-item {{ request()->routeIs('pppoe.*') ? 'active' : '' }}">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>
                            <path d="M22 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
                        </svg>
                    </span>
                    <span>PPPoE Users</span>
                </a>
                @endcan
                @can('view hotspot')
                <a href="{{ route('hotspot.index') }}" class="nav-item {{ request()->routeIs('hotspot.*') ? 'active' : '' }}">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1.42 9A16 16 0 0122.58 9M5 12.55a11 11 0 0114.08 0M10.54 16.1a6 6 0 012.92 0"/><circle cx="12" cy="20" r="1"/>
                        </svg>
                    </span>
                    <span>Hotspot</span>
                </a>
                @endcan
                @can('view queue')
                <a href="{{ route('queue.index') }}" class="nav-item {{ request()->routeIs('queue.*') ? 'active' : '' }}">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 6h18M3 12h18M3 18h18"/><path d="M3 9l3-3-3-3M3 21l3-3-3-3"/>
                        </svg>
                    </span>
                    <span>Queue / Bandwidth</span>
                </a>
                @endcan
                @can('view ip')
                <a href="{{ route('ip.index') }}" class="nav-item {{ request()->routeIs('ip.*') ? 'active' : '' }}">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 010 20M2 12h20"/>
                        </svg>
                    </span>
                    <span>IP & DHCP</span>
                </a>
                @endcan
                @can('view traffic')
                <a href="{{ route('traffic.index') }}" class="nav-item {{ request()->routeIs('traffic.index') ? 'active' : '' }}">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                        </svg>
                    </span>
                    <span>Traffic Monitor</span>
                </a>
                <a href="{{ route('traffic.mrtg') }}" class="nav-item {{ request()->routeIs('traffic.mrtg*') ? 'active' : '' }}">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/>
                            <path d="M6 10l3-3 3 3 3-5 3 3" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <span>MRTG Monitor</span>
                    <span class="nav-badge" style="background:rgba(0,212,255,0.15);color:#00d4ff;border:1px solid rgba(0,212,255,0.3);">NEW</span>
                </a>
                @endcan
            </div>

            <div class="nav-section">
                <span class="nav-section-label">BISNIS</span>
                @can('view customers')
                <a href="{{ route('customers.index') }}" class="nav-item {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 00-4-3.87M16 3.13a4 4 0 010 7.75"/>
                        </svg>
                    </span>
                    <span>Pelanggan</span>
                </a>
                @endcan
                <a href="{{ route('packages.index') }}" class="nav-item {{ request()->routeIs('packages.*') ? 'active' : '' }}">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                            <line x1="12" y1="22.08" x2="12" y2="12"></line>
                        </svg>
                    </span>
                    <span>Paket Layanan</span>
                </a>
                @can('view billing')
                <a href="{{ route('billing.index') }}" class="nav-item {{ request()->routeIs('billing.*') ? 'active' : '' }}">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>
                        </svg>
                    </span>
                    <span>Tagihan</span>
                </a>
                @endcan
                @can('view reports')
                <a href="{{ route('reports.index') }}" class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                        </svg>
                    </span>
                    <span>Laporan</span>
                </a>
                @endcan
                <a href="{{ route('whatsapp.voucher-requests') }}" class="nav-item {{ request()->routeIs('whatsapp.*') ? 'active' : '' }}">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/>
                        </svg>
                    </span>
                    <span>WA Voucher</span>
                    <span class="nav-badge" style="background:rgba(37,211,102,0.15);color:#25d366;border:1px solid rgba(37,211,102,0.3);">WA</span>
                </a>
                
                {{-- Telegram Menu --}}
                <div class="nav-item {{ request()->routeIs('telegram.*') ? 'active' : '' }}" style="display:block;height:auto;padding-right:0;">
                    <div style="display:flex;align-items:center;padding-right:16px;">
                        <span class="nav-icon" style="color:#00b0f0;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>
                            </svg>
                        </span>
                        <span style="flex:1;">Telegram Bot</span>
                    </div>
                    <div style="padding-left:42px;display:flex;flex-direction:column;gap:8px;margin-top:10px;">
                        <a href="{{ route('telegram.users') }}" style="color:var(--text-muted);font-size:13px;text-decoration:none;">👥 Pengguna & Saldo</a>
                        <a href="{{ route('telegram.requests') }}" style="color:var(--text-muted);font-size:13px;text-decoration:none;">📋 Riwayat Transaksi</a>
                        <a href="{{ route('telegram.settings') }}" style="color:var(--text-muted);font-size:13px;text-decoration:none;">⚙️ Pengaturan Bot</a>
                    </div>
                </div>
            </div>

            <div class="nav-section">
                <span class="nav-section-label">SISTEM</span>
                @can('view users')
                <a href="{{ route('users.index') }}" class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
                        </svg>
                    </span>
                    <span>Pengguna</span>
                </a>
                @endcan
                @can('view notifications')
                <a href="{{ route('notifications.index') }}" class="nav-item {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0"/>
                        </svg>
                    </span>
                    <span>Notifikasi</span>
                </a>
                @endcan
                @can('view settings')
                <a href="{{ route('settings.index') }}" class="nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
                        </svg>
                    </span>
                    <span>Pengaturan</span>
                </a>
                @endcan
            </div>
        </nav>

        {{-- User Profile --}}
        <div class="sidebar-footer">
            <div class="user-profile">
                <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="user-avatar">
                <div class="user-info">
                    <span class="user-name">{{ auth()->user()->name }}</span>
                    <span class="user-role">{{ auth()->user()->getRoleNames()->first() ?? 'User' }}</span>
                </div>
                <form action="{{ route('logout') }}" method="POST" class="logout-form">
                    @csrf
                    <button type="submit" class="btn-logout" title="Logout">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Main Content --}}
    <main class="main-content">
        {{-- Top Bar --}}
        <header class="topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </button>
                <div class="page-breadcrumb">
                    <h1 class="page-title">@yield('page-title', 'Dashboard')</h1>
                    @hasSection('breadcrumb')
                    <div class="breadcrumb">
                        @yield('breadcrumb')
                    </div>
                    @endif
                </div>
            </div>
            <div class="topbar-right">
                {{-- Router Status Indicator --}}
                <div class="router-status-indicator" id="routerStatusIndicator">
                    <span class="status-dot online"></span>
                    <span class="status-text">System Online</span>
                </div>

                {{-- Notification Bell --}}
                <button class="btn-icon" id="notifBtn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0"/>
                    </svg>
                    <span class="notif-badge">3</span>
                </button>

                {{-- User Avatar --}}
                <div class="topbar-user dropdown-container" style="position: relative;">
                    <div class="topbar-user-toggle" id="userDropdownToggle" style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                        <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="topbar-avatar">
                        <span class="topbar-username">{{ auth()->user()->name }}</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M6 9l6 6 6-6"/>
                        </svg>
                    </div>
                    
                    {{-- Dropdown Menu --}}
                    <div id="userDropdownMenu" class="user-dropdown" style="display: none; position: absolute; right: 0; top: 120%; background: var(--bg-primary, #ffffff); border: 1px solid var(--border-color, #e2e8f0); border-radius: 0.5rem; padding: 0.5rem; min-width: 150px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); z-index: 50;">
                        <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                            @csrf
                            <button type="submit" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; width: 100%; border: none; background: transparent; color: #ef4444; cursor: pointer; border-radius: 0.375rem; font-size: 0.875rem; font-weight: 500;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/>
                                </svg>
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        {{-- Flash Messages --}}
        @if(session('success'))
        <div class="alert alert-success" id="flashAlert">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
            {{ session('success') }}
            <button onclick="this.parentElement.remove()" class="alert-close">×</button>
        </div>
        @endif
        @if(session('error'))
        <div class="alert alert-error" id="flashAlert">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
            </svg>
            {{ session('error') }}
            <button onclick="this.parentElement.remove()" class="alert-close">×</button>
        </div>
        @endif

        {{-- Page Content --}}
        <div class="content-wrapper">
            @yield('content')
        </div>
    </main>

    {{-- Overlay for mobile sidebar --}}
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <script>
        // Sidebar toggle
        const sidebar = document.getElementById('sidebar');
        const toggle = document.getElementById('sidebarToggle');
        const overlay = document.getElementById('sidebarOverlay');

        toggle?.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            overlay.classList.toggle('active');
        });

        overlay?.addEventListener('click', () => {
            sidebar.classList.remove('collapsed');
            overlay.classList.remove('active');
        });

        // User dropdown toggle
        const userToggle = document.getElementById('userDropdownToggle');
        const userDropdown = document.getElementById('userDropdownMenu');
        
        userToggle?.addEventListener('click', (e) => {
            e.stopPropagation();
            if (userDropdown.style.display === 'none') {
                userDropdown.style.display = 'block';
            } else {
                userDropdown.style.display = 'none';
            }
        });

        document.addEventListener('click', (e) => {
            if (userDropdown && userDropdown.style.display === 'block' && !userToggle.contains(e.target) && !userDropdown.contains(e.target)) {
                userDropdown.style.display = 'none';
            }
        });

        // Auto-dismiss flash messages
        setTimeout(() => {
            document.getElementById('flashAlert')?.remove();
        }, 5000);
    </script>

    @stack('scripts')
</body>
</html>
