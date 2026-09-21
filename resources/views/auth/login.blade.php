<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – SIPADI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Lora:ital@1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --bg-main: #fff;
            --bg-secondary: #f0f7f3;
            --text-primary: #0f2218;
            --text-secondary: #7d9a8a;
            --text-muted: #4b6358;
            --border-color: #e2e8e4;
            --focus-ring: rgba(56,161,105,.12);
            --input-bg: #fff;
        }

        html.dark {
            --bg-main: #0d1f17;
            --bg-secondary: #112219;
            --text-primary: #e8f5ee;
            --text-secondary: #5c8a72;
            --text-muted: #6aaf8a;
            --border-color: #1e3d2b;
            --focus-ring: rgba(79,213,133,.15);
            --input-bg: #112219;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            background: #0f2218;
            overflow: hidden;
        }

        /* ───────────────────────────
           LEFT PANEL
        ─────────────────────────── */
        .panel-left {
            flex: 1;
            background: linear-gradient(135deg, #1a3a2a 0%, #0f2218 50%, #1e4d35 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px 40px;
            position: relative;
            overflow: hidden;
        }

        .panel-left::before {
            content: '';
            position: absolute;
            width: 500px; height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(56,161,105,.15) 0%, transparent 70%);
            top: -100px; right: -100px;
        }

        .panel-left::after {
            content: '';
            position: absolute;
            width: 300px; height: 300px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(79,213,133,.08) 0%, transparent 70%);
            bottom: -50px; left: -50px;
        }

        .grain {
            position: absolute;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)' opacity='0.04'/%3E%3C/svg%3E");
            opacity: .4;
            pointer-events: none;
        }

        /* === AURORA BLOBS === */
        .aurora {
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }

        .aurora-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(70px);
            animation: blobFloat 12s ease-in-out infinite alternate;
        }

        .aurora-blob-1 {
            width: 420px; height: 320px;
            background: radial-gradient(circle, rgba(79,213,133,.22) 0%, transparent 70%);
            top: -80px; left: -60px;
            animation-duration: 11s;
            animation-delay: 0s;
        }

        .aurora-blob-2 {
            width: 360px; height: 360px;
            background: radial-gradient(circle, rgba(56,161,105,.18) 0%, transparent 70%);
            bottom: 60px; right: -80px;
            animation-duration: 14s;
            animation-delay: -4s;
        }

        .aurora-blob-3 {
            width: 260px; height: 260px;
            background: radial-gradient(circle, rgba(16,185,98,.14) 0%, transparent 70%);
            top: 40%; left: 30%;
            animation-duration: 9s;
            animation-delay: -7s;
        }

        @keyframes blobFloat {
            0%   { transform: translate(0px, 0px) scale(1); }
            33%  { transform: translate(30px, -20px) scale(1.05); }
            66%  { transform: translate(-20px, 15px) scale(0.97); }
            100% { transform: translate(10px, 30px) scale(1.03); }
        }

        /* === GRID SHIMMER === */
        .grid-overlay {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(79,213,133,.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(79,213,133,.04) 1px, transparent 1px);
            background-size: 48px 48px;
            animation: gridPulse 6s ease-in-out infinite;
            pointer-events: none;
            z-index: 0;
        }

        @keyframes gridPulse {
            0%, 100% { opacity: 0.4; }
            50%       { opacity: 0.9; }
        }

        /* === FLOATING PARTICLES === */
        .particles {
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }

        .particle {
            position: absolute;
            border-radius: 50%;
            background: rgba(79, 213, 133, 0.6);
            animation: particleRise linear infinite;
        }

        @keyframes particleRise {
            0%   { transform: translateY(0) scale(1); opacity: 0; }
            10%  { opacity: 1; }
            90%  { opacity: 0.6; }
            100% { transform: translateY(-110vh) scale(0.5); opacity: 0; }
        }

        @media (prefers-reduced-motion: reduce) {
            .aurora-blob, .particle, .grid-overlay {
                animation: none !important;
            }
        }

        /* ───────────────────────────
           LEFT CONTENT
        ─────────────────────────── */
        .left-content { position: relative; z-index: 1; text-align: center; max-width: 380px; }

        .logo-big {
            width: 72px; height: 72px;
            background: linear-gradient(135deg, #4fd585, #38a169);
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            font-size: 34px;
            margin: 0 auto 28px;
            box-shadow: 0 8px 32px rgba(56,161,105,.4);
        }

        .brand-name {
            font-size: 36px;
            font-weight: 800;
            color: #fff;
            letter-spacing: -1px;
            margin-bottom: 8px;
        }

        .brand-tagline {
            font-family: 'Lora', serif;
            font-style: italic;
            color: rgba(255,255,255,.5);
            font-size: 14.5px;
            margin-bottom: 48px;
            line-height: 1.6;
        }

        .features {
            display: flex;
            flex-direction: column;
            gap: 16px;
            text-align: left;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 18px;
            background: rgba(255,255,255,.05);
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,.07);
        }

        .feature-icon {
            width: 38px; height: 38px;
            border-radius: 10px;
            background: rgba(56,161,105,.2);
            display: flex; align-items: center; justify-content: center;
            font-size: 16px;
            color: #4fd585;
            flex-shrink: 0;
        }

        .feature-text { font-size: 13px; color: rgba(255,255,255,.7); line-height: 1.4; }
        .feature-text strong { display: block; color: rgba(255,255,255,.9); font-size: 13.5px; }

        /* ───────────────────────────
           RIGHT PANEL
        ─────────────────────────── */
        .panel-right {
            width: 460px;
            background: var(--bg-main);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }

        .login-box { width: 100%; max-width: 360px; }

        .login-header { margin-bottom: 36px; }
        .login-header h2 { font-size: 26px; font-weight: 800; color: var(--text-primary); margin-bottom: 6px; }
        .login-header p { font-size: 13.5px; color: var(--text-secondary); }

        .form-group { margin-bottom: 18px; }

        label {
            display: block;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .input-wrap { position: relative; }

        .input-wrap i.icon-left {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 14px;
        }

        .input-wrap input {
            width: 100%;
            padding: 12px 14px 12px 40px;
            border: 1.5px solid var(--border-color);
            border-radius: 10px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 14px;
            color: var(--text-primary);
            background: var(--input-bg);
            transition: border-color .2s, box-shadow .2s;
            outline: none;
        }

        .input-wrap input::placeholder { color: var(--text-secondary); }

        .input-wrap input:focus {
            border-color: #38a169;
            box-shadow: 0 0 0 3px var(--focus-ring);
        }

        .input-wrap .toggle-pw {
            position: absolute;
            right: 13px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 14px;
            padding: 4px;
        }

        .remember-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }

        .remember-row label {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-muted);
            cursor: pointer;
            margin: 0;
        }

        .remember-row a {
            font-size: 13px;
            color: #38a169;
            text-decoration: none;
            font-weight: 600;
        }

        .btn-login {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #38a169, #2d7a52);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all .2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 14px rgba(56,161,105,.35);
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #2d7a52, #236040);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(56,161,105,.4);
        }

        .error-alert {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            background: #fee2e2;
            border: 1px solid #fecaca;
            border-radius: 10px;
            color: #991b1b;
            font-size: 13px;
            margin-bottom: 20px;
        }

        html.dark .error-alert {
            background: #2d1010;
            border-color: #5c1f1f;
            color: #f87171;
        }

        .login-footer {
            margin-top: 32px;
            text-align: center;
        }

        .login-footer p { font-size: 12px; color: var(--text-secondary); }

        .small-hint {
            color: var(--text-secondary);
            display: block;
            margin-top: 6px;
            font-size: 12px;
        }

        @media (max-width: 900px) {
            .panel-left { display: none; }
            .panel-right { width: 100%; }
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .login-box { animation: slideIn .4s ease; }

        /* ── DARK MODE OVERRIDES ── */
        html.dark body { background: #060e09; }
        html.dark .panel-right { background: #0f1f17; border-left: 1px solid #1e3d2a; }
        html.dark .login-header h2 { color: #d1fae5; }
        html.dark .login-header p { color: #4a9068; }
        html.dark label { color: #6fcf97; }
        html.dark .input-wrap input { background: #152b1e; border-color: #1e3d2a; color: #d1fae5; }
        html.dark .input-wrap input:focus { border-color: #3db370; box-shadow: 0 0 0 3px rgba(61,179,112,.15); }
        html.dark .input-wrap input::placeholder { color: #4a9068; }
        html.dark .input-wrap i.icon-left, html.dark .input-wrap .toggle-pw { color: #4a9068; }
        html.dark .remember-row label { color: #6fcf97; }
        html.dark .login-footer p { color: #4a9068; }

        /* ── Dark mode toggle ── */
        .login-dark-toggle {
            position: fixed;
            top: 16px; right: 16px;
            width: 40px; height: 40px;
            border-radius: 10px;
            border: 1.5px solid #e2e8e4;
            background: #f0f7f3;
            color: #2d7a52;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            font-size: 15px;
            z-index: 999;
            transition: all .2s;
        }

        .login-dark-toggle:hover { background: #dff0e8; color: #236040; border-color: #c6ddd3; }
        html.dark .login-dark-toggle { border-color: #1e3d2a; background: #152b1e; color: #6fcf97; }
        html.dark .login-dark-toggle:hover { background: #1a3326; color: #4fd585; }
        .login-dark-toggle .icon-sun  { display: none; }
        .login-dark-toggle .icon-moon { display: block; }
        html.dark .login-dark-toggle .icon-sun  { display: block; }
        html.dark .login-dark-toggle .icon-moon { display: none; }

        *, *::before, *::after {
            transition: background-color .25s ease, border-color .25s ease, color .1s ease;
        }

        /* ── SCROLLBAR STYLING ── */
        ::-webkit-scrollbar {
            width: 14px;
            height: 14px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(45, 122, 82, 0.35);
            border-radius: 8px;
            border: 3px solid transparent;
            background-clip: padding-box;
            transition: background .2s ease, box-shadow .2s ease;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(45, 122, 82, 0.7);
            background-clip: padding-box;
            box-shadow: 0 0 12px rgba(45, 122, 82, 0.3);
        }

        ::-webkit-scrollbar-thumb:active {
            background: rgba(45, 122, 82, 0.9);
        }

        html.dark ::-webkit-scrollbar-thumb {
            background: rgba(79, 213, 133, 0.4);
        }

        html.dark ::-webkit-scrollbar-thumb:hover {
            background: rgba(79, 213, 133, 0.85);
            box-shadow: 0 0 12px rgba(79, 213, 133, 0.5);
        }

        html.dark ::-webkit-scrollbar-thumb:active {
            background: rgba(79, 213, 133, 1);
        }
    </style>
</head>
<script>
(function(){
    var k = 'simhpsb_dark_mode';
    var s = localStorage.getItem(k);
    var dark = s !== null ? s === 'true' : (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
    if (dark) document.documentElement.classList.add('dark');
})();
</script>
<body>

<!-- Dark Mode Toggle -->
<button class="login-dark-toggle" id="darkModeToggle" title="Mode Gelap" aria-label="Toggle dark mode">
    <i class="fas fa-sun icon-sun"></i>
    <i class="fas fa-moon icon-moon"></i>
</button>

<!-- LEFT PANEL -->
<div class="panel-left">
    <div class="grain"></div>

    <!-- Aurora blobs -->
    <div class="aurora">
        <div class="aurora-blob aurora-blob-1"></div>
        <div class="aurora-blob aurora-blob-2"></div>
        <div class="aurora-blob aurora-blob-3"></div>
    </div>

    <!-- Grid shimmer -->
    <div class="grid-overlay"></div>

    <!-- Floating particles (diisi JS) -->
    <div class="particles" id="particles"></div>

    <div class="left-content">
        <div class="logo-big">
            <img src="https://raw.githubusercontent.com/NoahMikhailovna/foto/c45c72f9adca95001eefebd49d7581e89d0de508/padi_logo_fitted.svg"
                 alt="SIPADI" style="width:100%;height:100%;object-fit:contain;">
        </div>
        <div class="brand-name">SIPADI</div>
        <div class="brand-tagline">Sistem Informasi Pengelolaan Padi<br></div>

        <div class="features">
            <div class="feature-item">
                <div class="feature-icon"><i class="fas fa-chart-pie"></i></div>
                <div class="feature-text">
                    <strong>Dashboard Real-Time</strong>
                    Pantau stok gabah & beras kapan saja
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-icon"><i class="fas fa-bell"></i></div>
                <div class="feature-text">
                    <strong>Alert Otomatis</strong>
                    Notifikasi langsung saat stok menipis
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-icon"><i class="fas fa-file-export"></i></div>
                <div class="feature-text">
                    <strong>Laporan & Ekspor</strong>
                    Rekap panen dan margin per periode
                </div>
            </div>
        </div>
    </div>
</div>

<!-- RIGHT PANEL -->
<div class="panel-right">
    <div class="login-box">
        <div class="login-header">
            <h2>Selamat datang 👋</h2>
            <p>Masuk ke SIPADI. Gunakan akun Anda untuk melihat panel sesuai peran.</p>
        </div>

        @if($errors->any())
            <div class="error-alert">
                <i class="fas fa-exclamation-circle"></i>
                {{ $errors->first() }}
            </div>
        @endif

        @if(session('error'))
            <div class="error-alert">
                <i class="fas fa-exclamation-circle"></i>
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="form-group">
                <label for="identifier">Email, Nama Pengguna, atau Nama Petani</label>
                <div class="input-wrap">
                    <i class="fas fa-user icon-left"></i>
                    <input type="text" id="identifier" name="identifier"
                        value="{{ old('identifier') }}"
                        placeholder="Masukkan email, nama pengguna, atau nama petani"
                        required autofocus autocomplete="username">
                </div>
                <small class="small-hint">Gunakan email, username, atau nama petani untuk login.</small>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrap">
                    <i class="fas fa-lock icon-left"></i>
                    <input type="password" id="password" name="password"
                        placeholder="••••••••"
                        required autocomplete="current-password">
                    <button type="button" class="toggle-pw" onclick="togglePw()" tabindex="-1">
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <div class="remember-row">
                <label>
                    <input type="checkbox" name="remember" style="width:auto;margin:0;" {{ old('remember') ? 'checked' : '' }}>
                    Ingat saya
                </label>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i>
                Masuk ke Dashboard
            </button>
        </form>

        <div class="login-footer">
            <p>SIPADI &nbsp;·&nbsp; Kelompok Goat Pensiun</p>
            <p style="margin-top:4px;">Universitas Singaperbangsa Karawang</p>
        </div>
    </div>
</div>

<script>
function togglePw() {
    const pw = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');
    if (pw.type === 'password') {
        pw.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        pw.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

// Dark mode toggle
(function(){
    var STORAGE_KEY = 'simhpsb_dark_mode';
    var btn = document.getElementById('darkModeToggle');
    if (!btn) return;
    var isDark = document.documentElement.classList.contains('dark');
    btn.setAttribute('title', isDark ? 'Mode Terang' : 'Mode Gelap');
    btn.addEventListener('click', function(){
        var next = !document.documentElement.classList.contains('dark');
        if (next) document.documentElement.classList.add('dark');
        else document.documentElement.classList.remove('dark');
        localStorage.setItem(STORAGE_KEY, String(next));
        btn.setAttribute('title', next ? 'Mode Terang' : 'Mode Gelap');
    });
})();

// Floating particles
(function() {
    const container = document.getElementById('particles');
    if (!container) return;
    const count = 18;
    for (let i = 0; i < count; i++) {
        const p = document.createElement('div');
        p.className = 'particle';
        const size = Math.random() * 5 + 2;
        const left = Math.random() * 100;
        const delay = Math.random() * 16;
        const duration = Math.random() * 12 + 10;
        const startY = Math.random() * 80 + 20;
        p.style.cssText = `
            width: ${size}px;
            height: ${size}px;
            left: ${left}%;
            top: ${startY}%;
            animation-duration: ${duration}s;
            animation-delay: -${delay}s;
            opacity: 0;
            box-shadow: 0 0 ${size * 2}px rgba(79,213,133,0.5);
        `;
        container.appendChild(p);
    }
})();
</script>
</body>
</html>