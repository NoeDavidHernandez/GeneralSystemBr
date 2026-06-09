<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipo NLogic | SaaS Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg-primary: #f8fafc; --bg-secondary: #f1f5f9;
            --bg-card: rgba(255,255,255,0.9); --border-card: rgba(148,163,184,0.2);
            --text-primary: #0f172a; --text-secondary: #475569;
            --accent: #6366f1; --accent-gradient: linear-gradient(135deg, #6366f1, #4f46e5);
            --green: #10b981; --red: #ef4444;
            --card-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -2px rgba(0,0,0,0.05);
        }
        [data-theme="dark"] {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-card: rgba(30,41,59,0.7);
            --border-card: rgba(255,255,255,0.1);
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --card-shadow: 0 4px 6px -1px rgba(0,0,0,0.2);
        }
        body { font-family: 'Inter', sans-serif; background: var(--bg-primary); color: var(--text-primary); padding: 40px 20px; transition: all 0.3s ease; }
        .container { max-width: 1000px; margin: 0 auto; }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .header h1 { font-size: 1.8rem; font-weight: 800; }
        
        .nav-tabs { display:flex; gap:16px; margin-bottom:24px; border-bottom:1px solid var(--border-card); padding-bottom:12px; }
        .nav-tabs a { font-weight:500; text-decoration:none; padding-bottom:12px; color:var(--text-secondary); transition:color 0.2s; }
        .nav-tabs a:hover { color:var(--text-primary); }
        .nav-tabs a.active { color:var(--accent); font-weight:600; border-bottom:2px solid var(--accent); margin-bottom:-13px; }

        .card { background: var(--bg-card); border-radius: 16px; padding: 32px; box-shadow: var(--card-shadow); border: 1px solid var(--border-card); margin-bottom: 24px; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { text-align: left; padding: 12px; border-bottom: 2px solid var(--border-card); color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase; }
        td { padding: 16px 12px; border-bottom: 1px solid var(--border-card); vertical-align: middle; }
        
        .btn-outline { padding: 8px 16px; border-radius: 8px; border: 1px solid var(--border-card); background: transparent; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; }
        .btn-outline:hover { background: var(--bg-secondary); }
        
        .btn-delete { color: var(--red); background: rgba(239,68,68,0.1); border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .btn-delete:hover { background: var(--red); color: white; }

        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 8px; }
        .form-control { width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid var(--border-card); background: var(--bg-secondary); font-size: 0.95rem; }
        .btn-submit { padding: 10px 20px; background: var(--accent-gradient); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }

        .alert { padding: 16px; border-radius: 8px; margin-bottom: 24px; background: rgba(16,185,129,0.1); color: var(--green); border: 1px solid var(--green); font-weight:500; }
        .alert-error { background: rgba(239,68,68,0.1); color: var(--red); border-color: var(--red); }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👥 Equipo NLogic</h1>
            <div class="header-actions" style="display:flex; gap:12px; align-items:center;">
                <button class="btn-outline" onclick="toggleTheme()" style="padding: 8px; border-radius: 50%;">🌙</button>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-outline" style="color:var(--text-primary);">Cerrar Sesión</button>
                </form>
            </div>
        </div>

        <div class="nav-tabs">
            <a href="{{ route('superadmin.dashboard') }}">📊 Dashboard Global</a>
            <a href="{{ route('superadmin.team.index') }}" class="active">👥 Equipo NLogic</a>
        </div>

        @if(session('success'))
            <div class="alert">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
            <div class="card" style="margin-bottom:0;">
                <h3 style="margin-bottom: 16px;">Socios (SuperAdmins)</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Correo Electrónico</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($team as $member)
                        <tr>
                            <td><strong>{{ $member->name }}</strong> @if($member->id === auth()->id()) <span style="font-size:0.7rem; color:var(--accent);">(Tú)</span> @endif</td>
                            <td>{{ $member->email }}</td>
                            <td>
                                @if($member->id !== auth()->id())
                                <form action="{{ route('superadmin.team.destroy', $member->id) }}" method="POST" onsubmit="return confirm('¿Eliminar a este socio? Perderá acceso total al sistema SaaS.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-delete">Revocar Acceso</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="card" style="margin-bottom:0;">
                <h3 style="margin-bottom: 16px;">Agregar Socio</h3>
                <p style="font-size:0.85rem; color:var(--text-secondary); margin-bottom:24px;">Crea una cuenta con acceso total (SuperAdmin) para otro miembro de NLogic.</p>
                
                <form action="{{ route('superadmin.team.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Nombre Completo</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Correo Electrónico</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contraseña Temporal</label>
                        <input type="password" name="password" class="form-control" placeholder="Mínimo 8 caracteres" required>
                        <small style="color:var(--text-secondary); display:block; margin-top:4px;">Se le pedirá cambiarla al primer inicio de sesión.</small>
                    </div>
                    <div style="text-align:right;">
                        <button type="submit" class="btn-submit">Crear Cuenta</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        lucide.createIcons();
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
        function toggleTheme() {
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            if (isDark) {
                document.documentElement.setAttribute('data-theme', '');
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
            }
        }
    </script>
</body>
</html>
