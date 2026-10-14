<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MANTIK Manajemen MikroTik</title>
    <meta name="description" content="Login ke MANTIK - Sistem Manajemen MikroTik terpadu">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
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
            position: relative;
            overflow: hidden;
        }

        /* Animated background */
        .bg-grid {
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(0, 212, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 212, 255, 0.03) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
        }

        .bg-orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
            animation: float 8s ease-in-out infinite;
        }

        .orb-1 {
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(0, 212, 255, 0.12), transparent 70%);
            top: -100px;
            right: -100px;
            animation-delay: 0s;
        }

        .orb-2 {
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(0, 102, 255, 0.1), transparent 70%);
            bottom: -100px;
            left: -100px;
            animation-delay: -4s;
        }

        .orb-3 {
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(100, 0, 255, 0.08), transparent 70%);
            top: 50%;
            left: 30%;
            animation-delay: -2s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-30px) scale(1.05); }
        }

        /* Login Container */
        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 440px;
            padding: 20px;
        }

        /* Logo */
        .login-logo {
            text-align: center;
            margin-bottom: 32px;
        }

        .logo-icon-wrap {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, rgba(0,212,255,0.15), rgba(0,102,255,0.1));
            border: 1px solid rgba(0, 212, 255, 0.3);
            border-radius: 20px;
            margin: 0 auto 16px;
            filter: drop-shadow(0 0 20px rgba(0, 212, 255, 0.3));
        }

        .logo-icon-wrap svg { width: 40px; height: 40px; }

        .logo-name {
            display: block;
            font-size: 32px;
            font-weight: 900;
            background: linear-gradient(135deg, #00d4ff, #0066ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: 4px;
        }

        .logo-tagline {
            display: block;
            font-size: 13px;
            color: #4a5568;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-top: 4px;
        }

        /* Card */
        .login-card {
            background: rgba(13, 20, 36, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 36px;
            backdrop-filter: blur(20px);
            box-shadow:
                0 0 0 1px rgba(0, 212, 255, 0.05),
                0 20px 60px rgba(0, 0, 0, 0.5),
                inset 0 1px 0 rgba(255, 255, 255, 0.05);
        }

        .login-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .login-subtitle {
            font-size: 13px;
            color: #4a5568;
            margin-bottom: 28px;
        }

        /* Form */
        .form-group { margin-bottom: 20px; }

        .form-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: #8892b0;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #4a5568;
            pointer-events: none;
        }
        .input-icon svg { width: 16px; height: 16px; }

        .form-input {
            width: 100%;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            padding: 12px 14px 12px 42px;
            color: #e8eaf6;
            font-size: 14px;
            font-family: inherit;
            outline: none;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .form-input:focus {
            border-color: #00d4ff;
            background: rgba(0, 212, 255, 0.04);
            box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.12);
        }

        .form-input::placeholder { color: #4a5568; }

        .toggle-pass {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #4a5568;
            padding: 4px;
            display: flex;
            align-items: center;
            transition: color 0.2s;
        }
        .toggle-pass:hover { color: #00d4ff; }
        .toggle-pass svg { width: 16px; height: 16px; }

        .form-error {
            font-size: 12px;
            color: #ff4444;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* Remember & Forgot */
        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }

        .checkbox-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .checkbox-wrap input {
            width: 16px;
            height: 16px;
            accent-color: #00d4ff;
            cursor: pointer;
        }

        .checkbox-wrap label {
            font-size: 13px;
            color: #8892b0;
            cursor: pointer;
        }

        .forgot-link {
            font-size: 13px;
            color: #00d4ff;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.2s;
        }
        .forgot-link:hover { opacity: 0.8; }

        /* Submit Button */
        .btn-login {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #00d4ff, #0066ff);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-family: inherit;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            letter-spacing: 0.5px;
            transition: all 0.25s;
            box-shadow: 0 4px 20px rgba(0, 212, 255, 0.3);
            position: relative;
            overflow: hidden;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
            transition: left 0.5s;
        }

        .btn-login:hover::before { left: 100%; }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 30px rgba(0, 212, 255, 0.45);
        }

        .btn-login:active { transform: translateY(0); }

        /* Divider */
        .divider {
            text-align: center;
            position: relative;
            margin: 20px 0;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: rgba(255,255,255,0.07);
        }

        .divider span {
            position: relative;
            background: #0d1424;
            padding: 0 12px;
            font-size: 12px;
            color: #4a5568;
        }

        /* Demo credentials */
        .demo-creds {
            background: rgba(0, 212, 255, 0.05);
            border: 1px solid rgba(0, 212, 255, 0.15);
            border-radius: 10px;
            padding: 14px;
            margin-top: 20px;
        }

        .demo-creds-title {
            font-size: 11px;
            font-weight: 700;
            color: #00d4ff;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .demo-cred-row {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #8892b0;
            padding: 4px 0;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            cursor: pointer;
            border-radius: 4px;
            padding: 4px 4px;
            transition: background 0.2s;
        }
        .demo-cred-row:last-child { border-bottom: none; }
        .demo-cred-row:hover { background: rgba(0, 212, 255, 0.05); color: #e8eaf6; }
        .demo-cred-row span:last-child { color: #00d4ff; font-weight: 600; font-family: monospace; }

        /* Error alert */
        .alert-error {
            background: rgba(255, 68, 68, 0.1);
            border: 1px solid rgba(255, 68, 68, 0.3);
            border-radius: 10px;
            padding: 12px;
            font-size: 13px;
            color: #ff4444;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .alert-error svg { width: 16px; height: 16px; flex-shrink: 0; }

        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 24px;
            font-size: 12px;
            color: #4a5568;
        }
        .login-footer span { color: #00d4ff; }

        /* Animated particles */
        .particles {
            position: fixed;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
        }

        .particle {
            position: absolute;
            width: 2px;
            height: 2px;
            background: rgba(0, 212, 255, 0.5);
            border-radius: 50%;
            animation: particleFloat linear infinite;
        }

        @keyframes particleFloat {
            0% { transform: translateY(100vh) translateX(0); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-100px) translateX(50px); opacity: 0; }
        }
    </style>
</head>
<body>
    <div class="bg-grid"></div>
    <div class="orb-1 bg-orb"></div>
    <div class="orb-2 bg-orb"></div>
    <div class="orb-3 bg-orb"></div>

    {{-- Particles --}}
    <div class="particles" id="particles"></div>

    <div class="login-container">
        {{-- Logo --}}
        <div class="login-logo">
            <div class="logo-icon-wrap">
                <svg viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M16 2L4 8v16l12 6 12-6V8L16 2z" fill="url(#lg)" opacity="0.9"/>
                    <path d="M16 2v28M4 8l12 6 12-6" stroke="rgba(255,255,255,0.2)" stroke-width="0.5"/>
                    <circle cx="16" cy="14" r="4" fill="rgba(255,255,255,0.9)"/>
                    <defs>
                        <linearGradient id="lg" x1="4" y1="2" x2="28" y2="30" gradientUnits="userSpaceOnUse">
                            <stop offset="0%" stop-color="#00d4ff"/>
                            <stop offset="100%" stop-color="#0066ff"/>
                        </linearGradient>
                    </defs>
                </svg>
            </div>
            <span class="logo-name">MANTIK</span>
            <span class="logo-tagline">Manajemen MikroTik</span>
        </div>

        {{-- Login Card --}}
        <div class="login-card">
            <h1 class="login-title">Selamat Datang 👋</h1>
            <p class="login-subtitle">Masuk ke sistem manajemen router Anda</p>

            {{-- Error Messages --}}
            @if($errors->any())
            <div class="alert-error">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><circle cx="12" cy="16" r="0.5" fill="currentColor" stroke="none"/>
                </svg>
                {{ $errors->first() }}
            </div>
            @endif

            @if(session('status'))
            <div style="background: rgba(0,230,118,0.1); border: 1px solid rgba(0,230,118,0.3); border-radius: 10px; padding: 12px; font-size: 13px; color: #00e676; margin-bottom: 20px;">
                {{ session('status') }}
            </div>
            @endif

            <form method="POST" action="{{ route('login') }}" id="loginForm">
                @csrf

                {{-- Email --}}
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                        </span>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="form-input"
                            placeholder="nama@domain.com"
                            required
                            autofocus
                            autocomplete="email"
                        >
                    </div>
                    @error('email')
                    <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>
                            </svg>
                        </span>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            class="form-input"
                            placeholder="••••••••"
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" class="toggle-pass" onclick="togglePassword()" id="togglePassBtn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" id="eyeIcon">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                    <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Options --}}
                <div class="form-options">
                    <label class="checkbox-wrap">
                        <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                        <label for="remember">Ingat saya</label>
                    </label>
                    @if(Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="forgot-link">Lupa password?</a>
                    @endif
                </div>

                {{-- Submit --}}
                <button type="submit" class="btn-login" id="loginBtn">
                    Masuk ke Dashboard
                </button>
            </form>
        </div>

        {{-- Footer --}}
        <div class="login-footer">
            <p>MANTIK v1.0 &mdash; Powered by <span>Laravel 11</span> &amp; <span>RouterOS API</span></p>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('eyeIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
            } else {
                input.type = 'password';
                icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
            }
        }

        // Loading state on submit
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('loginBtn');
            btn.textContent = 'Memverifikasi...';
            btn.style.opacity = '0.8';
            btn.disabled = true;
        });

        // Generate particles
        const container = document.getElementById('particles');
        for (let i = 0; i < 20; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.animationDuration = (8 + Math.random() * 15) + 's';
            particle.style.animationDelay = (Math.random() * 15) + 's';
            particle.style.opacity = Math.random() * 0.5 + 0.1;
            particle.style.width = (Math.random() * 3 + 1) + 'px';
            particle.style.height = particle.style.width;
            container.appendChild(particle);
        }
    </script>
</body>
</html>
