<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $panelLabel = 'SIPADI';
        if (auth()->check()) {
            $role = auth()->user()->role;
            $panelLabel = $role === 'admin' ? 'Admin Panel' : ($role === 'petugas' ? 'Petugas Panel' : 'Petani Panel');
        }
    @endphp
    <title>SIPADI – @yield('title', $panelLabel)</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Lora:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <style>
        :root {
            --green-900: #1a3a2a;
            --green-800: #1e4d35;
            --green-700: #236040;
            --green-600: #2d7a52;
            --green-500: #38a169;
            --green-400: #4fd585;
            --green-300: #86efac;
            --green-100: #dcfce7;
            --green-50:  #f0fdf4;
            --amber-500: #f59e0b;
            --amber-100: #fef3c7;
            --red-500:   #ef4444;
            --red-100:   #fee2e2;
            --blue-500:  #3b82f6;
            --blue-100:  #dbeafe;
            --surface:   #ffffff;
            --surface-2: #f8faf9;
            --surface-3: #f1f5f2;
            --border:    #e2e8e4;
            --text-primary:   #0f2218;
            --text-secondary: #4b6358;
            --text-muted:     #7d9a8a;
            --sidebar-w: 260px;
            --header-h: 64px;
            --radius: 12px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
            --shadow-md: 0 4px 16px rgba(0,0,0,.08), 0 2px 6px rgba(0,0,0,.05);
            --shadow-lg: 0 12px 40px rgba(0,0,0,.12);
        }

        html.dark {
            --surface:   #0d1f17;
            --surface-2: #112219;
            --surface-3: #1a3325;
            --border:    #1e3d2b;
            --text-primary:   #e8f5ee;
            --text-secondary: #6aaf8a;
            --text-muted:     #5c8a72;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html {
            background: var(--surface);
            color: var(--text-primary);
            scroll-behavior: smooth;
        }

        /* ── SCROLLBAR LIGHT MODE ── */
        html:not(.dark) ::-webkit-scrollbar {
            width: 12px;
            height: 12px;
        }

        html:not(.dark) ::-webkit-scrollbar-track {
            background: transparent;
        }

        html:not(.dark) ::-webkit-scrollbar-thumb {
            background: rgba(45, 122, 82, 0.3);
            border-radius: 8px;
            border: 3px solid transparent;
            background-clip: padding-box;
            transition: background .2s ease;
        }

        html:not(.dark) ::-webkit-scrollbar-thumb:hover {
            background: rgba(45, 122, 82, 0.7);
            background-clip: padding-box;
        }

        /* ── SCROLLBAR DARK MODE ── */
        html.dark ::-webkit-scrollbar {
            width: 16px;
            height: 16px;
        }

        html.dark ::-webkit-scrollbar-track {
            background: rgba(13, 31, 23, 0.95);
        }

        html.dark ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #4CF693 0%, #35D07F 100%);
            border-radius: 12px;
            border: 3px solid transparent;
            background-clip: padding-box;
            transition: all .3s ease;
        }

        html.dark ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, #5DFF9F 0%, #45E08F 100%);
            background-clip: padding-box;
            box-shadow: 0 0 28px rgba(76, 246, 147, 0.9);
        }

        html.dark ::-webkit-scrollbar-thumb:active {
            background: linear-gradient(180deg, #6FFF9F 0%, #50E08F 100%);
            box-shadow: 0 0 32px rgba(76, 246, 147, 1);
        }

        body {
            background: var(--surface);
            color: var(--text-primary);
        }

        .modal {
            background: var(--surface);
            border-radius: 16px;
            width: calc(100% - 40px);
            max-width: 720px;
            box-shadow: var(--shadow-lg);
            transform: scale(.96) translateY(10px);
            transition: transform .2s;
            max-height: calc(100vh - 80px);
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            overflow: hidden; /* outer container hides overflow, inner body scrolls */
        }

        .modal-body {
            padding: 18px 20px;
            overflow: auto; /* enable internal scrolling */
            -webkit-overflow-scrolling: touch;
            flex: 1 1 auto; /* grow to fill available space */
            scrollbar-width: thin;
            scrollbar-color: rgba(0,0,0,.15) transparent;
        }

        .modal-body::-webkit-scrollbar {
            width: 10px;
        }

        .modal-body::-webkit-scrollbar-track {
            background: transparent;
        }

        .modal-body::-webkit-scrollbar-thumb {
            background: rgba(0,0,0,.2);
            border-radius: 8px;
            border: 2px solid transparent;
            background-clip: padding-box;
            transition: background .2s ease;
        }

        .modal-body::-webkit-scrollbar-thumb:hover {
            background: rgba(0,0,0,.4);
            background-clip: padding-box;
        }

        .modal-footer {
            flex-shrink: 0; /* keep footer visible */
        }

        .sidebar {
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: var(--sidebar-w);
            min-width: 260px;
            background: var(--green-700);
            color: #fff;
            overflow: hidden;
            transition: transform .3s ease;
            z-index: 100;
        }

        .sidebar-logo {
            padding: 24px 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }

        .logo-badge {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-icon {
            width: 42px; height: 42px;
            background: linear-gradient(135deg, var(--green-400), var(--green-600));
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
            box-shadow: 0 4px 12px rgba(56,161,105,.4);
        }

        .logo-text { line-height: 1.2; }
        .logo-text strong { display: block; font-size: 15px; font-weight: 800; color: #fff; letter-spacing: .3px; }
        .logo-text span { font-size: 10px; color: var(--green-300); text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }

        .sidebar-nav {
            flex: 1;
            min-height: 0;
            padding: 16px 12px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,.45) transparent;
        }

        .sidebar-nav::-webkit-scrollbar {
            width: 10px;
        }

        .sidebar-nav::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar-nav::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,.35);
            border-radius: 8px;
            border: 2px solid transparent;
            background-clip: padding-box;
            transition: background .2s ease;
        }

        .sidebar-nav::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,.65);
            background-clip: padding-box;
        }

        .nav-section-label {
            font-size: 10px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1.2px;
            padding: 8px 8px 6px;
            margin-top: 8px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 8px;
            color: rgba(255,255,255,.6);
            font-size: 13.5px;
            font-weight: 500;
            text-decoration: none;
            transition: all .2s;
            margin-bottom: 2px;
            position: relative;
        }

        .nav-item:hover { background: rgba(255,255,255,.07); color: #fff; }

        .nav-item.active {
            background: linear-gradient(135deg, var(--green-700), var(--green-600));
            color: #fff;
            box-shadow: 0 2px 8px rgba(56,161,105,.3);
        }

        .nav-item .icon {
            width: 32px; height: 32px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 7px;
            background: rgba(255,255,255,.06);
            font-size: 13px;
            flex-shrink: 0;
        }

        .nav-item.active .icon { background: rgba(255,255,255,.18); }

        .nav-badge {
            margin-left: auto;
            background: var(--red-500);
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 10px;
            min-width: 20px;
            text-align: center;
        }

        .sidebar-user {
            padding: 16px 12px;
            border-top: 1px solid rgba(255,255,255,.08);
        }

        .user-card {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            border-radius: 10px;
            background: rgba(255,255,255,.05);
        }

        .user-avatar {
            width: 36px; height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--green-400), var(--green-600));
            display: flex; align-items: center; justify-content: center;
            font-size: 13px;
            font-weight: 800;
            color: #fff;
            flex-shrink: 0;
        }

        .user-info { flex: 1; min-width: 0; }
        .user-name { font-size: 13px; font-weight: 600; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-role { font-size: 11px; color: var(--green-300); }

        .user-logout {
            width: 28px; height: 28px;
            border-radius: 7px;
            background: rgba(255,255,255,.06);
            display: flex; align-items: center; justify-content: center;
            color: rgba(255,255,255,.5);
            text-decoration: none;
            transition: all .2s;
            font-size: 13px;
        }
        .user-logout:hover { background: rgba(239,68,68,.25); color: #fca5a5; }

        /* ── MAIN CONTENT ── */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            height: var(--header-h);
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            padding: 0 28px;
            gap: 16px;
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: var(--shadow-sm);
        }

        .topbar-hamburger {
            display: none;
            background: none;
            border: none;
            font-size: 18px;
            color: var(--text-secondary);
            cursor: pointer;
        }
        
        /* Sidebar close button (one-click collapse) */
        .sidebar-close {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 36px; height: 36px;
            border-radius: 8px;
            border: none;
            background: rgba(255,255,255,.04);
            color: #fff;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            font-size: 14px;
            transition: background .12s, transform .12s;
            z-index: 150;
        }
        .sidebar-close:hover { background: rgba(255,255,255,.08); transform: translateX(-2px); }

        .topbar-breadcrumb {
            flex: 1;
        }

        .topbar-breadcrumb h1 {
            font-size: 17px;
            font-weight: 700;
            color: var(--text-primary);
        }

        .topbar-breadcrumb p {
            font-size: 12px;
            color: var(--text-muted);
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .dark-mode-toggle .icon-sun  { display: none; }
        .dark-mode-toggle .icon-moon { display: block; }
        html.dark .dark-mode-toggle .icon-sun  { display: block; }
        html.dark .dark-mode-toggle .icon-moon { display: none; }

        .topbar-btn {
            width: 38px; height: 38px;
            border-radius: 9px;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--text-secondary);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            font-size: 14px;
            transition: all .2s;
            position: relative;
            text-decoration: none;
        }
        .topbar-btn:hover { background: var(--surface-3); border-color: var(--green-300); color: var(--green-600); }

        .notif-dot {
            position: absolute;
            top: 6px; right: 6px;
            width: 8px; height: 8px;
            background: var(--red-500);
            border-radius: 50%;
            border: 2px solid var(--surface);
        }

        .content {
            flex: 1;
            padding: 28px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(45,122,82,.3) transparent;
        }

        .content::-webkit-scrollbar {
            width: 10px;
        }

        .content::-webkit-scrollbar-track {
            background: transparent;
        }

        .content::-webkit-scrollbar-thumb {
            background: rgba(45,122,82,.25);
            border-radius: 8px;
            border: 2px solid transparent;
            background-clip: padding-box;
            transition: background .2s ease;
        }

        .content::-webkit-scrollbar-thumb:hover {
            background: rgba(45,122,82,.5);
            background-clip: padding-box;
        }

        /* ── CARDS ── */
        .card {
            background: var(--surface);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .card-header {
            padding: 20px 24px 16px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .card-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-primary);
        }

        .card-subtitle {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .card-body { padding: 24px; }

        /* ── STAT CARDS ── */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--surface);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            padding: 20px;
            position: relative;
            overflow: hidden;
            transition: transform .2s, box-shadow .2s;
            box-shadow: var(--shadow-sm);
        }

        .stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
        }

        .stat-card.green::before { background: linear-gradient(90deg, var(--green-500), var(--green-400)); }
        .stat-card.amber::before { background: linear-gradient(90deg, var(--amber-500), #fbbf24); }
        .stat-card.red::before   { background: linear-gradient(90deg, var(--red-500), #f87171); }
        .stat-card.blue::before  { background: linear-gradient(90deg, var(--blue-500), #60a5fa); }

        .stat-icon {
            width: 44px; height: 44px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
            margin-bottom: 16px;
        }

        .stat-card.green .stat-icon { background: var(--green-100); color: var(--green-600); }
        .stat-card.amber .stat-icon { background: var(--amber-100); color: var(--amber-500); }
        .stat-card.red   .stat-icon { background: var(--red-100);   color: var(--red-500); }
        .stat-card.blue  .stat-icon { background: var(--blue-100);  color: var(--blue-500); }

        .stat-value {
            font-size: 26px;
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .stat-change {
            margin-top: 10px;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .stat-change.up   { color: var(--green-600); }
        .stat-change.down { color: var(--red-500); }

        /* ── TABLE ── */
        .table-container { 
            overflow-x: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(45,122,82,.3) transparent;
        }

        .table-container::-webkit-scrollbar {
            height: 8px;
        }

        .table-container::-webkit-scrollbar-track {
            background: transparent;
        }

        .table-container::-webkit-scrollbar-thumb {
            background: rgba(45,122,82,.25);
            border-radius: 4px;
            border: 1px solid transparent;
            background-clip: padding-box;
            transition: background .2s ease;
        }

        .table-container::-webkit-scrollbar-thumb:hover {
            background: rgba(45,122,82,.5);
            background-clip: padding-box;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13.5px;
        }

        table.data-table thead tr {
            background: var(--surface-2);
            border-bottom: 2px solid var(--border);
        }

        table.data-table th {
            padding: 12px 16px;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: .7px;
            white-space: nowrap;
        }

        table.data-table td {
            padding: 13px 16px;
            border-bottom: 1px solid var(--border);
            color: var(--text-primary);
            vertical-align: middle;
        }

        table.data-table tbody tr:hover { background: var(--surface-3); }
        table.data-table tbody tr:last-child td { border-bottom: none; }

        /* ── BADGES ── */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11.5px;
            font-weight: 600;
        }

        .badge-green  { background: var(--green-100); color: var(--green-600); }
        .badge-amber  { background: var(--amber-100); color: var(--amber-500); }
        .badge-red    { background: var(--red-100);   color: var(--red-500); }
        .badge-blue   { background: var(--blue-100);  color: var(--blue-500); }
        .badge-gray   { background: var(--surface-3); color: var(--text-secondary); }

        /* ── BUTTONS ── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: all .2s;
            line-height: 1;
        }

        .btn-primary {
            background: var(--green-600);
            color: #fff;
            box-shadow: 0 2px 8px rgba(56,161,105,.3);
        }
        .btn-primary:hover { background: var(--green-700); transform: translateY(-1px); }

        .btn-secondary {
            background: var(--surface);
            color: var(--text-secondary);
            border: 1px solid var(--border);
        }
        .btn-secondary:hover { background: var(--surface-3); color: var(--text-primary); }

        .btn-danger {
            background: var(--red-100);
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .btn-danger:hover { background: var(--red-500); color: #fff; }

        .btn-sm { padding: 6px 11px; font-size: 12px; }
        .btn-icon { width: 32px; height: 32px; padding: 0; justify-content: center; }

        /* ── FORM ── */
        .form-group { margin-bottom: 18px; }

        label {
            display: block;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 6px;
        }

        input[type=text], input[type=number], input[type=email],
        input[type=password], input[type=date], select, textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-family: inherit;
            font-size: 13.5px;
            color: var(--text-primary);
            background: var(--surface);
            transition: border-color .2s, box-shadow .2s;
            outline: none;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--green-500);
            box-shadow: 0 0 0 3px rgba(56,161,105,.12);
        }

        .form-hint { font-size: 11.5px; color: var(--text-muted); margin-top: 4px; }

        /* ── MODAL ── */
        .modal-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,.4);
            backdrop-filter: blur(4px);
            z-index: 200;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            opacity: 0;
            pointer-events: none;
            transition: opacity .2s;
        }

        .modal-overlay.open { opacity: 1; pointer-events: auto; }

        .modal {
            background: var(--surface);
            border-radius: 16px;
            width: calc(100% - 40px);
            max-width: 720px;
            box-shadow: var(--shadow-lg);
            transform: scale(.96) translateY(10px);
            transition: transform .2s;
            max-height: calc(100vh - 80px);
            overflow-y: auto;
        }

        .modal-overlay.open .modal { transform: scale(1) translateY(0); }

        .modal-header {
            padding: 22px 24px 18px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-title { font-size: 16px; font-weight: 700; }

        .modal-close {
            width: 32px; height: 32px;
            border-radius: 8px;
            border: none;
            background: var(--surface-3);
            color: var(--text-secondary);
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px;
            transition: all .2s;
        }
        .modal-close:hover { background: var(--red-100); color: var(--red-500); }

        .modal-body { padding: 18px 20px; }

        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        /* ── ALERT BANNER ── */
        .alert-banner {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 14px 18px;
            border-radius: var(--radius);
            margin-bottom: 20px;
            border-left: 4px solid;
        }

        .alert-banner.danger {
            background: var(--red-100);
            border-color: var(--red-500);
            color: #991b1b;
        }

        .alert-banner.warning {
            background: var(--amber-100);
            border-color: var(--amber-500);
            color: #92400e;
        }

        .alert-banner.success {
            background: var(--green-100);
            border-color: var(--green-500);
            color: var(--green-700);
        }

        /* ── PROGRESS BAR ── */
        .progress-bar {
            height: 8px;
            background: var(--surface-3);
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 10px;
            transition: width 1s ease;
        }

        /* ── SEARCH & FILTER BAR ── */
        .toolbar {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            padding: 16px 24px;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
        }

        .search-input-wrap {
            position: relative;
            flex: 1;
            min-width: 200px;
        }

        .search-input-wrap i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 13px;
        }

        .search-input-wrap input {
            padding-left: 36px;
            width: 100%;
        }

        /* ── GRID LAYOUTS ── */
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }

        /* ── RESPONSIVE ── */
        @media (max-width: 1100px) {
            .stat-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); width: min(280px, 90%); }
            .sidebar.open { transform: translateX(0); }
            .main { margin-left: 0; }
            .topbar-hamburger { display: flex; align-items: center; }
            .stat-grid { grid-template-columns: 1fr 1fr; }
            .grid-2, .grid-3 { grid-template-columns: 1fr; }
            .content { padding: 16px; }
        }

        @media (max-width: 480px) {
            .stat-grid { grid-template-columns: 1fr; }
        }

        /* ── CHART CONTAINER ── */
        .chart-wrap { position: relative; width: 100%; }

        /* ── TRANSITIONS / ANIMATIONS ── */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-12px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .animate-in {
            animation: fadeInUp .4s ease forwards;
        }

        .animate-in:nth-child(1) { animation-delay: .05s; opacity: 0; }
        .animate-in:nth-child(2) { animation-delay: .10s; opacity: 0; }
        .animate-in:nth-child(3) { animation-delay: .15s; opacity: 0; }
        .animate-in:nth-child(4) { animation-delay: .20s; opacity: 0; }

        /* ── EMPTY STATE ── */
        .empty-state {
            text-align: center;
            padding: 48px 24px;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 42px;
            margin-bottom: 14px;
            opacity: .4;
        }

        .empty-state h3 { font-size: 15px; color: var(--text-secondary); margin-bottom: 6px; }
        .empty-state p  { font-size: 13px; }
    </style>

    @stack('styles')
</head>
<!-- Dark Mode: jalankan SEBELUM paint untuk cegah flash of light mode -->
<script>
(function(){var k='simhpsb_dark_mode';var s=localStorage.getItem(k);var dark=s!==null?s==='true':(window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches);if(dark)document.documentElement.classList.add('dark');})();
</script>
<body>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <div class="logo-badge">
            <div class="logo-icon"><img src="https://raw.githubusercontent.com/NoahMikhailovna/foto/c45c72f9adca95001eefebd49d7581e89d0de508/padi_logo_fitted.svg" alt="SIMHP" style="width:100%;height:100%;object-fit:contain;"></div>
            <div class="logo-text">
                <strong>SIPADI</strong>
                <span>{{ $panelLabel }}</span>
            </div>
        </div>
    </div>

    <button class="sidebar-close" onclick="collapseSidebar()" title="Tutup sidebar">
        <i class="fas fa-chevron-left"></i>
    </button>

    @php
        $userRole = auth()->user()->role ?? 'guest';
        $routePrefix = $userRole === 'petugas' ? 'petugas.' : 'admin.';
        $dashboardRoute = $userRole === 'petani' ? route('petani.dashboard') : route($routePrefix.'dashboard');
    @endphp

    <nav class="sidebar-nav">
        <span class="nav-section-label">Utama</span>

        <a href="{{ $dashboardRoute }}" class="nav-item {{ request()->routeIs('admin.dashboard') || request()->routeIs('petugas.dashboard') || request()->routeIs('petani.dashboard') ? 'active' : '' }}">
            <span class="icon"><i class="fas fa-chart-pie"></i></span>
            Dashboard
        </a>

        @if($userRole !== 'petani')
            @role('admin')
                <span class="nav-section-label">Data Master</span>

                <a href="{{ route('admin.petani.index') }}" class="nav-item {{ request()->routeIs('admin.petani.*') ? 'active' : '' }}">
                    <span class="icon"><i class="fas fa-user-tie"></i></span>
                    Data Petani
                </a>

                <a href="{{ route('admin.pengguna.index') }}" class="nav-item {{ request()->routeIs('admin.pengguna.*') ? 'active' : '' }}">
                    <span class="icon"><i class="fas fa-users"></i></span>
                    Manajemen Pengguna
                </a>
            @endrole

            @if($userRole === 'petugas')
                <span class="nav-section-label">Data Master</span>

                <a href="{{ route('admin.petani.index') }}" class="nav-item {{ request()->routeIs('admin.petani.*') ? 'active' : '' }}">
                    <span class="icon"><i class="fas fa-user-tie"></i></span>
                    Data Petani
                </a>
            @endif

            <span class="nav-section-label">Transaksi</span>

            <a href="{{ route($routePrefix.'panen.index') }}" class="nav-item {{ request()->routeIs($routePrefix.'panen.*') ? 'active' : '' }}">
                <span class="icon"><i class="fas fa-seedling"></i></span>
                Pencatatan Panen
            </a>

            <a href="{{ route($routePrefix.'stok.index') }}" class="nav-item {{ request()->routeIs($routePrefix.'stok.*') ? 'active' : '' }}">
                <span class="icon"><i class="fas fa-warehouse"></i></span>
                Stok Gudang
            </a>

            <a href="{{ route('admin.tujuan-distribusi.index') }}" class="nav-item {{ request()->routeIs('admin.tujuan-distribusi.*') ? 'active' : '' }}">
                <span class="icon"><i class="fas fa-map-marker-alt"></i></span>
                Tujuan Distribusi
            </a>

            @role('admin')
                <span class="nav-section-label">Gudang & Harga</span>

                <a href="{{ route('admin.harga.index') }}" class="nav-item {{ request()->routeIs('admin.harga.*') ? 'active' : '' }}">
                    <span class="icon"><i class="fas fa-tags"></i></span>
                    Manajemen Harga
                </a>

                <a href="{{ route('admin.laporan.index') }}" class="nav-item {{ request()->routeIs('admin.laporan.*') ? 'active' : '' }}">
                    <span class="icon"><i class="fas fa-file-alt"></i></span>
                    Laporan
                </a>

                <a href="{{ route('admin.pengaturan') }}" class="nav-item {{ request()->routeIs('admin.pengaturan*') ? 'active' : '' }}">
                    <span class="icon"><i class="fas fa-cog"></i></span>
                    Pengaturan
                </a>
            @endrole

            @if(in_array($userRole, ['admin', 'petugas']))
                <span class="nav-section-label">Monitoring</span>

                @php $alertCount = $alertCount ?? \App\Models\Alert::whereIn('status', ['aktif', 'proses', 'dalam_penanganan'])->count(); @endphp
                <a href="{{ route($routePrefix.'alert.index') }}" class="nav-item {{ request()->routeIs($routePrefix.'alert.*') ? 'active' : '' }}">
                    <span class="icon"><i class="fas fa-bell"></i></span>
                    Alert Stok
                    @if($alertCount > 0)
                        <span class="nav-badge">{{ $alertCount }}</span>
                    @endif
                </a>
            @endif
        @endif
    </nav>

    <div class="sidebar-user">
        <div class="user-card">
            <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}</div>
            <div class="user-info">
                <div class="user-name">{{ auth()->user()->name ?? 'Admin' }}</div>
                <div class="user-role">{{ ucfirst(auth()->user()->role ?? 'admin') }}</div>
            </div>
            <a href="{{ route('logout') }}"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
               class="user-logout" title="Logout">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
</aside>

<form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">
    @csrf
</form>

<!-- MAIN -->
<div class="main">
    <!-- TOPBAR -->
    <header class="topbar">
        <button class="topbar-hamburger" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>

        <div class="topbar-breadcrumb">
            <h1>@yield('page-title', 'Dashboard')</h1>
            <p>@yield('page-subtitle', 'Sistem Informasi Monitoring Hasil Panen')</p>
        </div>

        <div class="topbar-actions">
            <!-- Dark Mode Toggle -->
            <button id="darkModeToggle" class="dark-mode-toggle topbar-btn" title="Mode Gelap" aria-label="Toggle dark mode">
                <i class="fas fa-sun icon-sun"></i>
                <i class="fas fa-moon icon-moon"></i>
            </button>

            @if($userRole !== 'petani')
                <a href="{{ route($routePrefix.'alert.index') }}" class="topbar-btn" title="Notifikasi">
                    <i class="fas fa-bell"></i>
                    @if(isset($alertCount) && $alertCount > 0)
                        <span class="notif-dot"></span>
                    @endif
                </a>
            @endif
        </div>
    </header>

    <!-- CONTENT -->
    <main class="content">
        @if(session('success'))
            <div class="alert-banner success">
                <i class="fas fa-check-circle"></i>
                <div>{{ session('success') }}</div>
            </div>
        @endif

        @if(session('error'))
            <div class="alert-banner danger">
                <i class="fas fa-exclamation-circle"></i>
                <div>{{ session('error') }}</div>
            </div>
        @endif

        @yield('content')
    </main>
</div>

<!-- GLOBAL MODAL CLOSE ON OVERLAY CLICK + SIDEBAR TOGGLE FUNCTIONS -->
<script>
    // modal overlay close
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', e => {
            if (e.target === overlay) overlay.classList.remove('open');
        });
    });

    function openModal(id) {
        document.getElementById(id).classList.add('open');
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('open');
    }

    // Sidebar collapse / toggle behavior
    function collapseSidebar() {
        // hide sidebar and remember collapsed state on body
        document.body.classList.add('sidebar-collapsed');
        document.getElementById('sidebar').classList.remove('open');
    }

    function expandSidebar() {
        document.body.classList.remove('sidebar-collapsed');
        // ensure sidebar opens on mobile
        document.getElementById('sidebar').classList.remove('open');
    }

    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const collapsed = document.body.classList.contains('sidebar-collapsed');
        if (collapsed) {
            // if collapsed, expand instead of toggling mobile open
            expandSidebar();
            return;
        }
        sidebar.classList.toggle('open');
    }
</script>

<!-- Dark Mode JS -->
<script src="{{ asset('js/dark-mode.js') }}"></script>

@if(in_array($userRole ?? 'guest', ['admin', 'petugas']))
    @include('chat-widget')
@endif

@stack('scripts')
</body>
</html>
