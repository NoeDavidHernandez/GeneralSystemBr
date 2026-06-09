<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SaaS Master Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg-primary: #f8fafc;
            --bg-secondary: #f1f5f9;
            --bg-card: rgba(255,255,255,0.9);
            --border-card: rgba(148,163,184,0.2);
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --accent: #6366f1;
            --accent-gradient: linear-gradient(135deg, #6366f1, #4f46e5);
            --green: #10b981;
            --red: #ef4444;
            --card-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -2px rgba(0,0,0,0.05);
            --glass-blur: blur(12px);
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

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            padding: 24px;
            transition: all 0.3s ease;
        }

        .container { max-width: 1200px; margin: 0 auto; }

        /* Header */
        .header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 32px;
            background: var(--bg-card);
            padding: 20px 24px;
            border-radius: 16px;
            border: 1px solid var(--border-card);
            backdrop-filter: var(--glass-blur);
            box-shadow: var(--card-shadow);
        }

        .header h1 { font-size: 1.5rem; font-weight: 700; color: var(--accent); }
        .header-actions { display: flex; gap: 12px; }

        .btn {
            padding: 10px 20px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer;
            transition: all 0.2s; text-decoration: none; display: inline-block;
        }
        .btn-outline { background: transparent; border: 1px solid var(--border-card); color: var(--text-primary); }
        .btn-logout { background: #ef4444; color: white; }
        .btn-logout:hover { background: #dc2626; }

        /* KPIs */
        .kpis {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px; margin-bottom: 32px;
        }

        .kpi-card {
            background: var(--bg-card); border: 1px solid var(--border-card);
            border-radius: 16px; padding: 24px; text-align: center;
            backdrop-filter: var(--glass-blur); box-shadow: var(--card-shadow);
        }

        .kpi-card h3 { font-size: 0.85rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
        .kpi-card .value { font-size: 2.5rem; font-weight: 800; color: var(--text-primary); }
        
        .kpi-card.purple .value { color: var(--accent); }
        .kpi-card.green .value { color: var(--green); }

        /* Table */
        .table-container {
            background: var(--bg-card); border: 1px solid var(--border-card);
            border-radius: 16px; overflow: hidden;
            backdrop-filter: var(--glass-blur); box-shadow: var(--card-shadow);
        }

        .table-header { padding: 20px; border-bottom: 1px solid var(--border-card); }
        .table-header h2 { font-size: 1.2rem; }

        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 16px 20px; border-bottom: 1px solid var(--border-card); }
        th { font-size: 0.85rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; background: rgba(0,0,0,0.02); }
        td { font-size: 0.95rem; }
        tr:last-child td { border-bottom: none; }

        .status-badge {
            display: inline-block; padding: 4px 12px; border-radius: 100px;
            font-size: 0.75rem; font-weight: 600;
        }
        .status-active { background: rgba(16,185,129,0.1); color: var(--green); }
        .status-inactive { background: rgba(239,68,68,0.1); color: var(--red); }

        .btn-toggle {
            padding: 6px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; border: none; cursor: pointer;
        }
        .btn-suspend { background: rgba(239,68,68,0.1); color: var(--red); }
        .btn-suspend:hover { background: var(--red); color: white; }
        .btn-activate { background: rgba(16,185,129,0.1); color: var(--green); }
        .btn-activate:hover { background: var(--green); color: white; }

        .alert {
            padding: 16px; border-radius: 8px; margin-bottom: 24px;
            background: rgba(16,185,129,0.1); color: var(--green); border: 1px solid var(--green);
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>👑 Panel Maestro (SaaS)</h1>
            <div class="header-actions">
                <div style="text-align: right; margin-right: 15px; font-size: 0.85rem; color: var(--text-secondary);">
                    <strong style="color: var(--text-primary); display: block;">Modo Super Admin</strong>
                    {{ Auth::user()->name }}
                </div>
                <button class="btn btn-outline" onclick="toggleTheme()">🌙</button>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-logout">Cerrar Sesión</button>
                </form>
            </div>
        </header>

        <div style="display:flex; gap:16px; margin-bottom:24px; border-bottom:1px solid var(--border-card); padding-bottom:12px;">
            <a href="{{ route('superadmin.dashboard') }}" style="color:var(--accent); font-weight:600; text-decoration:none; border-bottom:2px solid var(--accent); padding-bottom:12px; margin-bottom:-13px;">📊 Dashboard Global</a>
            <a href="{{ route('superadmin.team.index') }}" style="color:var(--text-secondary); font-weight:500; text-decoration:none; padding-bottom:12px;">👥 Equipo NLogic</a>
        </div>

        @if(session('success'))
            <div class="alert">{{ session('success') }}</div>
        @endif

        <div class="kpis">
            <div class="kpi-card purple">
                <h3>Total Barberías</h3>
                <div class="value">{{ $totalBarberias }}</div>
            </div>
            <div class="kpi-card green">
                <h3>Barberías Activas</h3>
                <div class="value">{{ $barberiasActivas }}</div>
            </div>
            <div class="kpi-card">
                <h3>Citas Hoy (Global)</h3>
                <div class="value">{{ $citasHoy }}</div>
            </div>
            <div class="kpi-card">
                <h3>Ingresos (Global)</h3>
                <div class="value">${{ number_format($ingresosGlobales, 2) }}</div>
            </div>
        </div>

        <div class="charts-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin-bottom: 32px;">
            <div class="table-container" style="padding: 20px; grid-column: 1 / -1;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                    <h3 style="font-size: 1rem; color: var(--text-secondary); margin:0;">Crecimiento NLogic (Nuevas Barberías)</h3>
                    <select id="rangoCrecimiento" style="padding:6px 12px; border-radius:6px; border:1px solid var(--border-card); background:var(--bg-secondary); color:var(--text-primary); font-size:0.85rem; font-weight:500; cursor:pointer; outline:none;">
                        <option value="1">1 Mes</option>
                        <option value="3">3 Meses</option>
                        <option value="6" selected>6 Meses</option>
                        <option value="12">1 Año</option>
                    </select>
                </div>
                <div style="position: relative; height: 250px; width: 100%;">
                    <canvas id="crecimientoChart"></canvas>
                </div>
            </div>
            <div class="table-container" style="padding: 20px;">
                <h3 style="margin-bottom: 16px; font-size: 1rem; color: var(--text-secondary);">Ingresos por Barbería</h3>
                <div style="position: relative; height: 250px; width: 100%;">
                    <canvas id="ingresosChart"></canvas>
                </div>
            </div>
            <div class="table-container" style="padding: 20px;">
                <h3 style="margin-bottom: 16px; font-size: 1rem; color: var(--text-secondary);">Volumen de Citas</h3>
                <div style="position: relative; height: 250px; width: 100%;">
                    <canvas id="citasChart"></canvas>
                </div>
            </div>
            <div class="table-container" style="padding: 20px;">
                <h3 style="margin-bottom: 16px; font-size: 1rem; color: var(--text-secondary);">Estado de Citas</h3>
                <div style="position: relative; height: 250px; width: 100%;">
                    <canvas id="estadosChart"></canvas>
                </div>
            </div>
        </div>

        <div class="table-container">
            <div class="table-header">
                <h2>Directorio de Inquilinos</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Barbería</th>
                        <th>Antigüedad</th>
                        <th>Referido Por</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($barberias as $b)
                    <tr>
                        <td>#{{ $b->id }}</td>
                        <td>
                            <strong>{{ $b->nombre }}</strong><br>
                            <span style="font-size:0.8rem; color:var(--text-secondary);">{{ $b->telefono ?? 'S/T' }}</span>
                        </td>
                        <td>{{ number_format($b->created_at->floatDiffInMonths(now()), 1) }} meses</td>
                        <td>
                            @if($b->referenciador)
                                <span style="font-size:0.85rem; color:var(--accent);">{{ $b->referenciador->nombre }}</span>
                            @else
                                <span style="font-size:0.85rem; color:var(--text-secondary);">-</span>
                            @endif
                        </td>
                        <td>
                            @if($b->activo)
                                <span class="status-badge status-active">Activa</span>
                            @else
                                <span class="status-badge status-inactive">Suspendida</span>
                            @endif
                        </td>
                        <td style="display:flex; gap:8px;">
                            <a href="{{ route('superadmin.negocios.show', $b->id) }}" class="btn-toggle" style="background:var(--bg-secondary); color:var(--text-primary); text-decoration:none;">Administrar</a>
                            
                            <form action="{{ route('superadmin.barberias.toggle', $b->id) }}" method="POST" onsubmit="return confirm('¿Seguro que quieres cambiar el estado de esta barbería?');">
                                @csrf
                                @if($b->activo)
                                    <button type="submit" class="btn-toggle btn-suspend">Suspender</button>
                                @else
                                    <button type="submit" class="btn-toggle btn-activate">Activar</button>
                                @endif
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Cargar tema guardado
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
            // Recargar para que Chart.js actualice colores si se desea
            window.location.reload();
        }

        document.addEventListener('DOMContentLoaded', function() {
            let crecimientoChartInstance = null;
            let ingresosChartInstance = null;
            let citasChartInstance = null;
            let estadosChartInstance = null;

            function loadData(rango = 6) {
                fetch(`/superadmin/datos?rango=${rango}`)
                    .then(response => response.json())
                    .then(data => {
                        const colors = [
                            'rgba(99, 102, 241, 0.8)', // Indigo
                            'rgba(16, 185, 129, 0.8)', // Emerald
                            'rgba(245, 158, 11, 0.8)', // Amber
                            'rgba(239, 68, 68, 0.8)',  // Red
                            'rgba(139, 92, 246, 0.8)'  // Violet
                        ];

                        // Gráfica de Crecimiento SaaS (Línea)
                        if(data.crecimiento) {
                            if(crecimientoChartInstance) crecimientoChartInstance.destroy();
                            crecimientoChartInstance = new Chart(document.getElementById('crecimientoChart'), {
                                type: 'line',
                                data: {
                                    labels: data.crecimiento.labels,
                                    datasets: [{
                                        label: 'Nuevas Barberías',
                                        data: data.crecimiento.data,
                                        borderColor: 'rgba(99, 102, 241, 1)',
                                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                                        borderWidth: 3,
                                        tension: 0.4,
                                        fill: true,
                                        pointBackgroundColor: 'rgba(99, 102, 241, 1)',
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: { legend: { display: false } },
                                    scales: {
                                        y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: 'rgba(148,163,184,0.1)' } },
                                        x: { grid: { display: false } }
                                    }
                                }
                            });
                        }

                    // Gráfica de Ingresos (Barras)
                    if(ingresosChartInstance) ingresosChartInstance.destroy();
                    ingresosChartInstance = new Chart(document.getElementById('ingresosChart'), {
                        type: 'bar',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Ingresos Totales ($)',
                                data: data.ingresos,
                                backgroundColor: colors,
                                borderRadius: 8
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { beginAtZero: true, grid: { color: 'rgba(148,163,184,0.1)' } },
                                x: { grid: { display: false } }
                            }
                        }
                    });

                    // Gráfica de Volumen de Citas (Doughnut)
                    if(citasChartInstance) citasChartInstance.destroy();
                    citasChartInstance = new Chart(document.getElementById('citasChart'), {
                        type: 'doughnut',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                data: data.totalCitas,
                                backgroundColor: colors,
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '70%',
                            plugins: {
                                legend: { position: 'bottom' }
                            }
                        }
                    });

                        // Gráfica de Estados (Barras Apiladas / Multiples)
                        if(estadosChartInstance) estadosChartInstance.destroy();
                        estadosChartInstance = new Chart(document.getElementById('estadosChart'), {
                            type: 'bar',
                            data: {
                                labels: data.labels,
                                datasets: [
                                    {
                                        label: 'Completadas',
                                        data: data.estados.completadas,
                                        backgroundColor: 'rgba(16, 185, 129, 0.8)', // Emerald
                                        borderRadius: 4
                                    },
                                    {
                                        label: 'Canceladas',
                                        data: data.estados.canceladas,
                                        backgroundColor: 'rgba(239, 68, 68, 0.8)', // Red
                                        borderRadius: 4
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    x: { stacked: false, grid: { display: false } },
                                    y: { stacked: false, beginAtZero: true, grid: { color: 'rgba(148,163,184,0.1)' } }
                                }
                            }
                        });
                    });
            }

            // Inicializar carga de datos con el valor actual del select
            const selectRango = document.getElementById('rangoCrecimiento');
            loadData(selectRango.value);

            // Escuchar cambios en el selector para recargar solo los datos
            selectRango.addEventListener('change', function() {
                loadData(this.value);
            });
        });
    </script>
</body>
</html>
