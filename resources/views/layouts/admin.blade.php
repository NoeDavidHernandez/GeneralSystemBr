<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Panel de Administración') — BarberOS</title>
    <meta name="description" content="Panel de administración para tu barbería.">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.min.js" defer></script>
    @stack('scripts')
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg-primary: #f8fafc;
            --bg-sidebar: #ffffff;
            --bg-card: #ffffff;
            --bg-card-hover: #f8fafc;
            --border-color: #e2e8f0;
            --text-primary: #0f172a;
            --text-secondary: #64748b;
            --accent: #2563eb;
            --accent-gradient: linear-gradient(135deg, #3b82f6, #1d4ed8);
            --accent-hover: #1d4ed8;
            --accent-btn-text: #ffffff;
            --sidebar-active-bg: #eff6ff;
            --sidebar-active-text: #2563eb;
            --green: #10b981; --red: #ef4444; --blue: #3b82f6; --purple: #8b5cf6; --orange: #f97316;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025);
        }
        [data-theme="dark"] {
            --bg-primary: #0f172a;
            --bg-sidebar: #1e293b;
            --bg-card: #1e293b;
            --bg-card-hover: #334155;
            --border-color: #334155;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --accent: #3b82f6;
            --accent-gradient: linear-gradient(135deg, #60a5fa, #3b82f6);
            --accent-hover: #60a5fa;
            --accent-btn-text: #ffffff;
            --sidebar-active-bg: #0f172a;
            --sidebar-active-text: #60a5fa;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            transition: background 0.3s ease, color 0.3s ease;
        }
        /* ─── SIDEBAR ─── */
        .sidebar {
            width: 260px;
            background: var(--bg-sidebar);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            left: 0; top: 0;
            transition: all 0.3s ease;
            z-index: 100;
        }
        .sidebar-header {
            padding: 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex; align-items: center; gap: 12px;
        }
        .sidebar-header .logo-icon {
            background: var(--accent-gradient);
            color: white; width: 36px; height: 36px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
        }
        .sidebar-header h2 { font-size: 1.2rem; font-weight: 700; color: var(--text-primary); }
        .sidebar-nav {
            padding: 20px 16px; flex: 1;
            display: flex; flex-direction: column; gap: 8px;
        }
        .nav-item {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 16px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 10px;
            font-weight: 500; font-size: 0.95rem;
            transition: all 0.2s ease;
        }
        .nav-item:hover { background: var(--bg-card-hover); color: var(--text-primary); }
        .nav-item.active { background: var(--sidebar-active-bg); color: var(--sidebar-active-text); font-weight: 600; }
        .nav-item i { width: 20px; height: 20px; }
        .sidebar-footer {
            padding: 24px 16px;
            border-top: 1px solid var(--border-color);
            display: flex; flex-direction: column; gap: 12px;
        }
        /* ─── MAIN CONTENT ─── */
        .main-content {
            flex: 1;
            margin-left: 260px;
            display: flex; flex-direction: column;
            min-height: 100vh;
            width: calc(100% - 260px);
            /* FIX: evita que el contenido se desborde horizontalmente */
            overflow-x: hidden;
        }
        /* ─── TOP HEADER ─── */
        .top-header {
            height: 72px;
            background: var(--bg-sidebar);
            border-bottom: 1px solid var(--border-color);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 32px;
            position: sticky; top: 0; z-index: 90;
        }
        .search-bar {
            display: flex; align-items: center; gap: 8px;
            background: var(--bg-primary);
            padding: 8px 16px;
            border-radius: 100px;
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            width: 300px;
        }
        .search-bar input {
            background: transparent; border: none; outline: none;
            color: var(--text-primary); width: 100%; font-family: 'Inter'; font-size: 0.9rem;
        }
        .header-right { display: flex; align-items: center; gap: 16px; }
        .theme-toggle {
            width: 40px; height: 40px; border-radius: 50%;
            border: 1px solid var(--border-color);
            background: transparent; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            color: var(--text-secondary); transition: all 0.2s;
        }
        .theme-toggle:hover { background: var(--bg-primary); color: var(--text-primary); }
        .user-profile {
            display: flex; align-items: center; gap: 12px;
            padding: 6px 12px; border-radius: 100px;
            border: 1px solid var(--border-color); cursor: pointer;
        }
        .user-profile .avatar {
            width: 32px; height: 32px; border-radius: 50%;
            background: var(--accent-gradient);
            color: white; display: flex; align-items: center; justify-content: center;
            font-weight: 600; font-size: 0.85rem;
        }
        .user-profile .name { font-size: 0.9rem; font-weight: 600; color: var(--text-primary); }
        /* ─── CONTENT AREA ─── */
        .content-area {
            padding: 32px; flex: 1;
            overflow-y: auto;
            /* FIX: asegura que el contenido no se desborde */
            overflow-x: hidden;
            width: 100%;
        }
        /* ─── GLOBALS ─── */
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; font-weight: 600; font-size: 0.875rem; border: none; border-radius: 10px; cursor: pointer; transition: all 0.2s ease; text-decoration: none; font-family: 'Inter'; }
        .btn-primary { background: var(--accent-gradient); color: var(--accent-btn-text); }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: var(--shadow-md); }
        .btn-danger { background: var(--red); color: white; }
        .card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 24px; box-shadow: var(--shadow-sm); transition: all 0.3s ease; }
        .page-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; }
        .page-title h1 { font-size: 1.8rem; font-weight: 700; color: var(--text-primary); }
        .page-title p { color: var(--text-secondary); margin-top: 4px; font-size: 0.95rem; }
        @media(max-width: 900px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; width: 100%; }
        }

        /* ─── MODALS & FORMS ─── */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15,23,42,0.6); backdrop-filter: blur(4px); z-index: 1000; display: none; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s ease; }
        .modal-overlay.active { display: flex; opacity: 1; }
        .modal { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; width: 90%; max-width: 500px; padding: 24px; box-shadow: var(--shadow-lg); transform: scale(0.95); transition: transform 0.3s ease; max-height: 90vh; overflow-y: auto; }
        .modal-overlay.active .modal { transform: scale(1); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-title { font-size: 1.25rem; font-weight: 700; display: flex; align-items: center; gap: 8px; color: var(--text-primary); }
        .modal-close { background: none; border: none; color: var(--text-secondary); cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .modal-close:hover { color: var(--red); }
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; color: var(--text-primary); }
        .form-control { width: 100%; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: 10px; background: var(--bg-primary); color: var(--text-primary); font-family: inherit; font-size: 0.9rem; transition: border-color 0.3s ease; }
        .form-control:focus { outline: none; border-color: var(--accent); }
        .checkbox-group { display: flex; flex-direction: column; gap: 8px; max-height: 180px; overflow-y: auto; padding: 12px; border: 1px solid var(--border-color); border-radius: 10px; background: var(--bg-primary); }
        .checkbox-label { display: flex; align-items: center; gap: 8px; font-size: 0.9rem; cursor: pointer; color: var(--text-primary); }
    </style>
    {{-- FIX CRÍTICO: @stack fuera del bloque <style> para que los estilos de las vistas --}}
    {{-- no queden atrapados dentro del CSS del layout --}}
    @stack('styles')
</head>
<body>

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo-icon"><i data-lucide="scissors"></i></div>
            <h2>{{ Auth::user()->barberia->nombre ?? 'SaaS' }}</h2>
        </div>
        <nav class="sidebar-nav">
            <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i data-lucide="layout-dashboard"></i> Dashboard
            </a>
            <a href="{{ route('admin.agenda') }}" class="nav-item {{ request()->routeIs('admin.agenda') ? 'active' : '' }}">
                <i data-lucide="calendar"></i> Agenda
            </a>
            @if(Auth::user()->rol === 'admin')
            <a href="{{ route('admin.clientes.index') }}" class="nav-item {{ request()->routeIs('admin.clientes.*') ? 'active' : '' }}">
                <i data-lucide="users"></i> Clientes
            </a>
            @endif
            <a href="{{ route('admin.configuracion.index') }}" class="nav-item {{ request()->routeIs('admin.configuracion.*') ? 'active' : '' }}">
                <i data-lucide="settings"></i> Configuración
            </a>
            @if(Auth::user()->rol === 'admin')
            <a href="{{ route('admin.empleados.index') }}" class="nav-item {{ request()->routeIs('admin.empleados.*') ? 'active' : '' }}">
                <i data-lucide="briefcase"></i> Empleados
            </a>
            @endif
        </nav>
        <div class="sidebar-footer">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="nav-item" style="width: 100%; border: none; background: transparent; cursor: pointer; text-align: left; padding: 12px 16px;">
                    <i data-lucide="log-out"></i> Cerrar Sesión
                </button>
            </form>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <header class="top-header">
            <div class="search-bar">
                <i data-lucide="search" style="width: 18px; height: 18px;"></i>
                <input type="text" placeholder="Buscar clientes, citas...">
            </div>
            <div class="header-right">
                <button class="theme-toggle" onclick="toggleTheme()" id="theme-toggle" title="Cambiar tema">
                    <i data-lucide="moon"></i>
                </button>
                <div class="user-profile">
                    <div class="avatar">{{ substr(auth()->user()->name ?? 'A', 0, 1) }}</div>
                    <span class="name">{{ auth()->user()->name ?? 'Administrador' }}</span>
                </div>
            </div>
        </header>

        <div class="content-area">
            @yield('content')
        </div>
    </main>

    @stack('modals')

    <script>
        let isDark = localStorage.getItem('theme') === 'dark';
        function applyTheme() {
            if (isDark) {
                document.documentElement.setAttribute('data-theme', 'dark');
            } else {
                document.documentElement.removeAttribute('data-theme');
            }
            const btn = document.getElementById('theme-toggle');
            if(btn) btn.innerHTML = isDark ? '<i data-lucide="sun"></i>' : '<i data-lucide="moon"></i>';
            if(typeof lucide !== 'undefined') lucide.createIcons();
            window.dispatchEvent(new Event('themeChanged'));
        }
        function toggleTheme() {
            isDark = !isDark;
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            applyTheme();
        }
        document.addEventListener('DOMContentLoaded', () => { applyTheme(); });
    </script>
    @stack('scripts-bottom')
</body>
</html>