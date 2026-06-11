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
        
        .btn-filter { padding: 6px 12px; border-radius: 6px; border: 1px solid var(--border-card); background: var(--bg-secondary); color: var(--text-secondary); font-size: 0.85rem; font-weight: 500; cursor: pointer; transition: all 0.2s; }
        .btn-filter:hover { background: rgba(99, 102, 241, 0.1); color: var(--accent); }
        .btn-filter.active { background: var(--accent); color: white; border-color: var(--accent); }

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
                    <h3 style="font-size: 1rem; color: var(--text-secondary); margin:0;">Métricas NLogic SaaS</h3>
                    <div class="filter-buttons" style="display: flex; gap: 8px;">
                        <button class="btn-filter active" data-rango="1">1 Mes</button>
                        <button class="btn-filter" data-rango="3">3 Meses</button>
                        <button class="btn-filter" data-rango="6">6 Meses</button>
                        <button class="btn-filter" data-rango="12">1 Año</button>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px;">
                    <div>
                        <h4 style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 8px; text-align: center;">Crecimiento (Nuevas Barberías)</h4>
                        <div style="position: relative; height: 250px; width: 100%;">
                            <canvas id="crecimientoChart"></canvas>
                        </div>
                    </div>
                    <div>
                        <h4 style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 8px; text-align: center;">Ingresos SaaS ($)</h4>
                        <div style="position: relative; height: 250px; width: 100%;">
                            <canvas id="ingresosNLogicChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-container" style="padding: 20px;">
                <h3 style="margin-bottom: 16px; font-size: 1rem; color: var(--text-secondary);">Próximos Pagos (Suscripciones)</h3>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    @forelse($proximosPagos as $pago)
                        @php
                            $diasRestantes = now()->startOfDay()->diffInDays($pago->fecha_proximo_pago, false);
                            $isVencido = $diasRestantes < 0;
                            $color = $isVencido ? 'var(--red)' : ($diasRestantes <= 5 ? '#f59e0b' : 'var(--green)');
                        @endphp
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border-radius: 8px; background: var(--bg-secondary); border: 1px solid var(--border-card);">
                            <div>
                                <div style="font-weight: 600; font-size: 0.9rem;">{{ $pago->nombre }}</div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 4px;">{{ $pago->fecha_proximo_pago->format('d M, Y') }}</div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-weight: 700; font-size: 0.9rem; color: {{ $color }};">
                                    @if($isVencido)
                                        Vencido (hace {{ abs((int)$diasRestantes) }} días)
                                    @elseif($diasRestantes == 0)
                                        ¡Hoy!
                                    @else
                                        En {{ (int)$diasRestantes }} días
                                    @endif
                                </div>
                                <a href="{{ route('superadmin.negocios.show', $pago->id) }}" style="font-size: 0.75rem; color: var(--accent); text-decoration: none; display: inline-block; margin-top: 4px;">Cobrar →</a>
                            </div>
                        </div>
                    @empty
                        <div style="text-align: center; color: var(--text-secondary); font-size: 0.85rem; padding: 20px;">No hay fechas registradas.</div>
                    @endforelse
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
            @if($errors->any())
                <div style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); border-radius: 8px; padding: 15px; margin: 20px; color: var(--red);">
                    <ul style="margin: 0; padding-left: 20px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="table-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h2>Directorio de Inquilinos</h2>
                <button onclick="document.getElementById('modalNuevoNegocio').style.display='flex'" style="padding: 8px 16px; border-radius: 8px; border: none; background: var(--accent); color: white; cursor: pointer; font-weight: 600; font-size: 0.9rem;">+ Registrar Negocio</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Barbería</th>
                        <th>Antigüedad</th>
                        <th>Próximo Pago</th>
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
                        <td>{{ number_format($b->created_at->floatDiffInMonths(now()), 1) }} meses<br><span style="font-size:0.75rem; color:var(--text-secondary);">Llegó: {{ $b->created_at->format('d/m/y') }}</span></td>
                        <td>
                            @if($b->fecha_proximo_pago)
                                <div style="font-weight: 500;">{{ $b->fecha_proximo_pago->format('d/m/Y') }}</div>
                                @if($b->recompensas_acumuladas > 0)
                                    <span style="display:inline-block; margin-top:4px; font-size:0.7rem; background:rgba(16,185,129,0.1); color:var(--green); padding:2px 6px; border-radius:4px; border:1px solid rgba(16,185,129,0.2);">
                                        +{{ $b->recompensas_acumuladas }} días gratis
                                    </span>
                                @endif
                            @else
                                <span style="color:var(--text-secondary);">-</span>
                            @endif
                        </td>
                        <td>
                            @if($b->referenciador)
                                <span style="font-size:0.85rem; color:var(--accent);">{{ $b->referenciador->nombre }}</span>
                            @else
                                <span style="font-size:0.85rem; color:var(--text-secondary);">-</span>
                            @endif
                        </td>
                        <td>
                            @if($b->activo)
                                @if($b->enPrueba())
                                    <span class="status-badge" style="background:rgba(245,158,11,0.1); color:#f59e0b; border:1px solid rgba(245,158,11,0.2);">Prueba 15D</span>
                                @else
                                    <span class="status-badge status-active">Activa</span>
                                @endif
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
            window.location.reload();
        }

        document.addEventListener('DOMContentLoaded', function() {
            let crecimientoChartInstance = null;
            let ingresosNLogicChartInstance = null;
            let citasChartInstance = null;
            let estadosChartInstance = null;

            function renderCharts(rango) {
                fetch(`{{ route('superadmin.datos') }}?rango=${rango}`)
                    .then(r => r.json())
                    .then(data => {
                        const colors = ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4'];

                        if(crecimientoChartInstance) crecimientoChartInstance.destroy();
                        if(data.crecimiento) {
                            crecimientoChartInstance = new Chart(document.getElementById('crecimientoChart'), {
                                type: 'line',
                                data: {
                                    labels: data.crecimiento.labels.reverse(),
                                    datasets: [{
                                        label: 'Nuevas Barberías',
                                        data: data.crecimiento.data.reverse(),
                                        borderColor: '#6366f1',
                                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                                        borderWidth: 2,
                                        fill: true,
                                        tension: 0.4,
                                        pointRadius: 4,
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

                        if(ingresosNLogicChartInstance) ingresosNLogicChartInstance.destroy();
                        if(data.ingresosNLogic) {
                            ingresosNLogicChartInstance = new Chart(document.getElementById('ingresosNLogicChart'), {
                                type: 'line',
                                data: {
                                    labels: data.ingresosNLogic.labels.reverse(),
                                    datasets: [{
                                        label: 'Ingresos ($)',
                                        data: data.ingresosNLogic.data.reverse(),
                                        borderColor: '#10b981',
                                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                        borderWidth: 2,
                                        fill: true,
                                        tension: 0.4,
                                        pointRadius: 4,
                                        pointBackgroundColor: 'rgba(16, 185, 129, 1)',
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
                        }

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
                                plugins: { legend: { position: 'bottom' } }
                            }
                        });

                        if(estadosChartInstance) estadosChartInstance.destroy();
                        estadosChartInstance = new Chart(document.getElementById('estadosChart'), {
                            type: 'bar',
                            data: {
                                labels: data.labels,
                                datasets: [
                                    { label: 'Completadas', data: data.estados.completadas, backgroundColor: '#10b981', borderRadius: 4 },
                                    { label: 'Canceladas', data: data.estados.canceladas, backgroundColor: '#ef4444', borderRadius: 4 }
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

            const filterButtons = document.querySelectorAll('.btn-filter');
            filterButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    filterButtons.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    renderCharts(this.getAttribute('data-rango'));
                });
            });

            renderCharts(6);
        });
    </script>

    <!-- Modal Nuevo Negocio -->
    <div id="modalNuevoNegocio" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000; backdrop-filter: blur(4px);">
        <div style="background: var(--bg-card); padding: 30px; border-radius: 16px; width: 90%; max-width: 500px; border: 1px solid var(--border-card); box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="color: var(--text-primary); font-size: 1.25rem;">Registrar Nuevo Negocio</h3>
                <button onclick="document.getElementById('modalNuevoNegocio').style.display='none'" style="background: none; border: none; color: var(--text-secondary); font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            
            <form action="{{ route('superadmin.negocios.store') }}" method="POST">
                @csrf
                <div style="margin-bottom: 15px;">
                    <label style="display: block; color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 6px;">Nombre de la Barbería</label>
                    <input type="text" name="nombre" required style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--border-card); background: var(--bg-secondary); color: var(--text-primary);">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 6px;">Teléfono WhatsApp</label>
                    <input type="text" name="telefono" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--border-card); background: var(--bg-secondary); color: var(--text-primary);">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 6px;">Email del Administrador</label>
                    <input type="email" name="email" required style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--border-card); background: var(--bg-secondary); color: var(--text-primary);">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 6px;">WhatsApp Phone ID (Meta)</label>
                    <input type="text" name="whatsapp_phone_id" required style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--border-card); background: var(--bg-secondary); color: var(--text-primary);">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 6px;">WhatsApp Token (Meta)</label>
                    <textarea name="whatsapp_token" required rows="2" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--border-card); background: var(--bg-secondary); color: var(--text-primary); resize: vertical;"></textarea>
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 6px;">Número WhatsApp Admin (Alertas)</label>
                    <input type="text" name="whatsapp_admin_numero" required placeholder="Ej: 521XXXXXXXXXX" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--border-card); background: var(--bg-secondary); color: var(--text-primary);">
                </div>

                <div style="margin-bottom: 25px;">
                    <label style="display: block; color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 6px;">Días de Prueba Iniciales</label>
                    <select name="dias_prueba" required style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--border-card); background: var(--bg-secondary); color: var(--text-primary);">
                        <option value="7">7 Días</option>
                        <option value="14">14 Días</option>
                        <option value="15" selected>15 Días</option>
                        <option value="21">21 Días</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="document.getElementById('modalNuevoNegocio').style.display='none'" style="padding: 12px 20px; border-radius: 8px; border: 1px solid var(--border-card); background: var(--bg-secondary); color: var(--text-secondary); cursor: pointer;">Cancelar</button>
                    <button type="submit" style="padding: 12px 20px; border-radius: 8px; border: none; background: var(--accent); color: white; font-weight: 600; cursor: pointer;">Registrar Negocio</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
