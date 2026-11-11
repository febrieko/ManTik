<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Pelanggan - {{ config('app.name', 'MANTIK') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: #080d1a;
            color: #e8eaf6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }
        .bg-grid {
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(37, 211, 102, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 212, 255, 0.03) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
        }
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(90px);
            pointer-events: none;
        }
        .orb-1 {
            width: 420px; height: 420px;
            background: radial-gradient(circle, rgba(37, 211, 102, 0.14), transparent 70%);
            top: -120px; right: -100px;
        }
        .orb-2 {
            width: 380px; height: 380px;
            background: radial-gradient(circle, rgba(0, 212, 255, 0.12), transparent 70%);
            bottom: -120px; left: -100px;
        }
        .portal-card {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
            background: rgba(15, 23, 42, 0.88);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 36px 28px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.45);
        }
        .brand {
            text-align: center;
            margin-bottom: 28px;
        }
        .brand-icon {
            display: inline-flex;
            width: 60px; height: 60px;
            background: linear-gradient(135deg, #25d366, #00d4ff);
            border-radius: 16px;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
            box-shadow: 0 10px 25px rgba(37, 211, 102, 0.25);
        }
        .brand h1 {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.3px;
            margin-bottom: 6px;
        }
        .brand p {
            font-size: 13px;
            color: #94a3b8;
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.35);
            color: #fca5a5;
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 7px;
        }
        .form-control {
            width: 100%;
            background: rgba(2, 6, 23, 0.65);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 11px;
            padding: 12px 14px;
            color: #f8fafc;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control:focus {
            border-color: #25d366;
            box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.15);
        }
        .remember-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
            font-size: 13px;
            color: #94a3b8;
        }
        .remember-row label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, #25d366, #128c7e);
            color: #fff;
            border: none;
            border-radius: 11px;
            padding: 13px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.2s;
            box-shadow: 0 8px 20px rgba(37, 211, 102, 0.28);
        }
        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 26px rgba(37, 211, 102, 0.35);
        }
        .footer-links {
            margin-top: 24px;
            padding-top: 18px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
        }
        .footer-links a {
            color: #00d4ff;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="bg-grid"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    <div class="portal-card">
        <div class="brand">
            <div class="brand-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2" width="30" height="30">
                    <path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/>
                    <path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/>
                </svg>
            </div>
            <h1>Portal Pelanggan</h1>
            <p>Masuk menggunakan Nomor WhatsApp &amp; Password Anda</p>
        </div>

        @if($errors->any())
            <div class="alert-error">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form action="{{ route('portal.login.submit') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label" for="phone">Nomor WhatsApp</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone') }}" class="form-control" placeholder="Contoh: 081234567890" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password Anda" required>
            </div>

            <div class="remember-row">
                <label>
                    <input type="checkbox" name="remember" value="1">
                    <span>Ingat perangkat ini</span>
                </label>
            </div>

            <button type="submit" class="btn-submit">Masuk ke Portal</button>
        </form>

        <div class="footer-links">
            <div>Belum punya akun portal? Hubungi Admin untuk aktivasi nomor WhatsApp Anda.</div>
            <div style="margin-top:10px;">
                Staff / Admin? <a href="{{ route('login') }}">Login Admin di sini</a>
            </div>
        </div>
    </div>
</body>
</html>

