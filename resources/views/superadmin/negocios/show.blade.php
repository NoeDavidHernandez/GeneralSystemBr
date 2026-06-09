<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar: {{ $barberia->nombre }} | NLogic</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
        .container { max-width: 800px; margin: 0 auto; }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .header h1 { font-size: 1.5rem; font-weight: 800; display:flex; align-items:center; gap:12px; }
        .btn-back { color: var(--text-secondary); text-decoration: none; font-weight: 600; display:flex; align-items:center; gap:8px; }
        .btn-back:hover { color: var(--accent); }

        .card {
            background: var(--bg-card); border-radius: 16px; padding: 32px;
            box-shadow: var(--card-shadow); border: 1px solid var(--border-card);
            margin-bottom: 24px;
        }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 32px; }
        .info-item label { display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary); margin-bottom: 4px; font-weight: 600; }
        .info-item div { font-size: 1.05rem; font-weight: 500; color: var(--text-primary); }

        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 8px; }
        .form-control { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--border-card); background: var(--bg-secondary); color: var(--text-primary); font-size: 0.95rem; font-family: 'Inter', sans-serif; }
        .form-control:focus { outline: none; border-color: var(--accent); }
        
        .btn-submit { padding: 12px 24px; background: var(--accent-gradient); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: transform 0.2s; }
        .btn-submit:hover { transform: translateY(-2px); }

        .alert { padding: 16px; border-radius: 8px; margin-bottom: 24px; background: rgba(16,185,129,0.1); color: var(--green); border: 1px solid var(--green); font-weight:500; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><a href="{{ route('superadmin.dashboard') }}" class="btn-back">← Volver</a> {{ $barberia->nombre }}</h1>
            <button onclick="toggleTheme()" style="padding: 8px; border-radius: 50%; border:1px solid var(--border-card); background:transparent; cursor:pointer;">🌙</button>
        </div>

        @if(session('success'))
            <div class="alert">{{ session('success') }}</div>
        @endif

        <div class="card">
            <h3 style="margin-bottom: 20px; border-bottom: 1px solid var(--border-card); padding-bottom: 10px;">Información del Negocio</h3>
            <div class="info-grid">
                <div class="info-item">
                    <label>Teléfono de Contacto</label>
                    <div>{{ $barberia->telefono ?? 'No registrado' }}</div>
                </div>
                <div class="info-item">
                    <label>Fecha de Registro</label>
                    <div>{{ $barberia->created_at->format('d M Y') }} (Hace {{ number_format($barberia->created_at->floatDiffInMonths(now()), 1) }} meses)</div>
                </div>
                <div class="info-item">
                    <label>Especialistas Registrados</label>
                    <div>{{ $barberia->barberos()->count() }} barberos</div>
                </div>
                <div class="info-item">
                    <label>Volumen Histórico</label>
                    <div>{{ $barberia->citas()->count() }} citas procesadas</div>
                </div>
            </div>

            <h3 style="margin-bottom: 20px; border-bottom: 1px solid var(--border-card); padding-bottom: 10px; margin-top: 40px;">Administración NLogic</h3>
            <form action="{{ route('superadmin.negocios.update', $barberia->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label class="form-label">Fecha de Próximo Pago (Suscripción)</label>
                    <input type="date" name="fecha_proximo_pago" class="form-control" value="{{ $barberia->fecha_proximo_pago ? $barberia->fecha_proximo_pago->format('Y-m-d') : '' }}">
                    <small style="color:var(--text-secondary); display:block; margin-top:6px;">Usa este campo para llevar el control manual de tu cobranza.</small>
                </div>

                <div class="form-group">
                    <label class="form-label">¿Fue referido por otro negocio?</label>
                    <select name="referido_por" id="referidoPorSelect" class="form-control" onchange="toggleRecompensa()">
                        <option value="">Ninguno / Llegó solo</option>
                        @foreach($barberiasActivas as $otra)
                            <option value="{{ $otra->id }}" {{ $barberia->referido_por == $otra->id ? 'selected' : '' }}>
                                {{ $otra->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group" id="recompensaDiv" style="display: none; background: rgba(99, 102, 241, 0.05); padding: 16px; border-radius: 8px; border: 1px dashed var(--accent);">
                    <label class="form-label" style="color: var(--accent);">Días gratis para el negocio que lo recomendó</label>
                    <input type="number" name="dias_recompensa" class="form-control" min="0" max="30" value="15">
                    <small style="color:var(--text-secondary); display:block; margin-top:6px;">Ingresa un valor (ej. 7 a 15). Se sumarán automáticamente a la fecha de pago del referenciador al guardar.</small>
                </div>

                <div style="text-align: right; margin-top: 32px;">
                    <button type="submit" class="btn-submit">Guardar Cambios</button>
                </div>
            </form>
        </div>

        <div class="card" style="margin-top: 24px;">
            <h3 style="margin-bottom: 20px; border-bottom: 1px solid var(--border-card); padding-bottom: 10px;">Registrar Pago (Suscripción SaaS)</h3>
            <form action="{{ route('superadmin.negocios.pagos.store', $barberia->id) }}" method="POST">
                @csrf
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                    <div class="form-group">
                        <label class="form-label">Monto ($)</label>
                        <input type="number" name="monto" class="form-control" step="0.01" required placeholder="Ej. 999.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fecha del Pago</label>
                        <input type="date" name="fecha_pago" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Método</label>
                        <select name="metodo" class="form-control">
                            <option value="Transferencia">Transferencia</option>
                            <option value="Efectivo">Efectivo</option>
                            <option value="Tarjeta">Tarjeta</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Notas (Opcional)</label>
                    <input type="text" name="notas" class="form-control" placeholder="Algún detalle adicional...">
                </div>
                <div style="text-align: right; margin-top: 16px;">
                    <button type="submit" class="btn-submit" style="background-color: var(--green);">Registrar Pago</button>
                </div>
            </form>

            <h4 style="margin-top: 40px; margin-bottom: 16px;">Historial de Pagos</h4>
            @if(count($pagos) > 0)
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-card);">
                            <th style="padding: 12px 8px; color: var(--text-secondary); font-size: 0.85rem;">Fecha</th>
                            <th style="padding: 12px 8px; color: var(--text-secondary); font-size: 0.85rem;">Monto</th>
                            <th style="padding: 12px 8px; color: var(--text-secondary); font-size: 0.85rem;">Método</th>
                            <th style="padding: 12px 8px; color: var(--text-secondary); font-size: 0.85rem;">Notas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pagos as $p)
                            <tr style="border-bottom: 1px solid var(--border-card);">
                                <td style="padding: 12px 8px;">{{ $p->fecha_pago->format('d/m/Y') }}</td>
                                <td style="padding: 12px 8px; font-weight: 500; color: var(--green);">${{ number_format($p->monto, 2) }}</td>
                                <td style="padding: 12px 8px;">{{ $p->metodo }}</td>
                                <td style="padding: 12px 8px; font-size: 0.85rem; color: var(--text-secondary);">{{ $p->notas }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p style="color: var(--text-secondary); font-size: 0.9rem;">No hay pagos registrados aún.</p>
            @endif
        </div>
    </div>
    <script>
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

        // Mostrar caja de recompensa solo si se selecciona un referenciador diferente al actual
        const selectElement = document.getElementById('referidoPorSelect');
        const recompensaDiv = document.getElementById('recompensaDiv');
        const originalReferidoVal = selectElement.value;

        function toggleRecompensa() {
            if (selectElement.value && selectElement.value !== originalReferidoVal) {
                recompensaDiv.style.display = 'block';
            } else {
                recompensaDiv.style.display = 'none';
            }
        }
        // Inicializar
        toggleRecompensa();
    </script>
</body>
</html>
