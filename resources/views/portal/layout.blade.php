<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Portal Pelanggan') - {{ config('app.name', 'MANTIK') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-main: #080d1a;
            --bg-card: rgba(15, 23, 42, 0.85);
            --border: rgba(255, 255, 255, 0.09);
            --text-primary: #f8fafc;
            --text-muted: #94a3b8;
            --accent: #25d366;
            --cyan: #00d4ff;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-main);
            color: var(--text-primary);
            min-height: 100vh;
            line-height: 1.5;
        }
        .portal-nav {
            background: rgba(15, 23, 42, 0.92);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 40;
        }
        .portal-nav-inner {
            max-width: 1140px;
            margin: 0 auto;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .nav-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 800;
            font-size: 16px;
            color: #fff;
            text-decoration: none;
        }
        .nav-badge {
            width: 34px; height: 34px;
            border-radius: 10px;
            background: linear-gradient(135deg, #25d366, #00d4ff);
            display: flex; align-items: center; justify-content: center;
        }
        .nav-user {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 13px;
        }
        .container {
            max-width: 1140px;
            margin: 0 auto;
            padding: 24px 20px 60px;
        }
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border-radius: 10px;
            padding: 9px 16px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid transparent;
            text-decoration: none;
            transition: opacity 0.15s, transform 0.1s;
        }
        .btn:hover { opacity: 0.92; }
        .btn-primary {
            background: linear-gradient(135deg, #25d366, #128c7e);
            color: #fff;
        }
        .btn-cyan {
            background: linear-gradient(135deg, #00d4ff, #0284c7);
            color: #fff;
        }
        .btn-outline {
            background: transparent;
            border-color: var(--border);
            color: var(--text-primary);
        }
        .form-control {
            width: 100%;
            background: rgba(2, 6, 23, 0.7);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 10px 12px;
            color: #fff;
            font-size: 13px;
            outline: none;
        }
        .form-control:focus { border-color: var(--accent); }
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid var(--border);
            font-size: 13px;
        }
        th {
            color: var(--text-muted);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge {
            display: inline-block;
            padding: 3px 9px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-success { background: rgba(37, 211, 102, 0.15); color: #4ade80; }
        .badge-warning { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }
        .badge-danger  { background: rgba(239, 68, 68, 0.15); color: #f87171; }
    </style>
</head>
<body>
    <nav class="portal-nav">
        <div class="portal-nav-inner">
            <a href="{{ route('portal.dashboard') }}" class="nav-brand">
                <span class="nav-badge">
                    <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2" width="18" height="18">
                        <path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/>
                        <path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/>
                    </svg>
                </span>
                <span>MANTIK Portal</span>
            </a>
            <div class="nav-user">
                <div style="text-align:right;">
                    <div style="font-weight:600;">{{ auth()->user()->name }}</div>
                    <div style="font-size:11px;color:var(--text-muted);">{{ auth()->user()->phone }}</div>
                </div>
                <form action="{{ route('portal.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline" style="padding:7px 12px;font-size:12px;">Keluar</button>
                </form>
            </div>
        </div>
    </nav>

    <main class="container">
        @yield('content')
    </main>
</body>
</html>

