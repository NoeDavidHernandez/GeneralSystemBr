<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración — Barbería</title>
    <meta name="description" content="Panel de administración para tu barbería. Citas pendientes, ingresos y estadísticas.">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg-primary: #f0f4fa;
            --bg-card: rgba(255,255,255,0.9);
            --bg-card-hover: rgba(255,255,255,0.98);
            --border-card: rgba(100,140,200,0.15);
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --accent: #2563eb;
            --accent-gradient: linear-gradient(135deg, #3b82f6, #1d4ed8);
            --accent-btn-text: #ffffff;
            --green: #059669;
            --red: #dc2626;
            --blue: #2563eb;
            --purple: #7c3aed;
            --orange: #ea580c;
            --kpi-shadow: 0 4px 20px rgba(37,99,235,0.08);
            --card-shadow: 0 2px 12px rgba(0,0,0,0.05);
            --card-hover-shadow: 0 12px 40px rgba(37,99,235,0.12);
            --bg-glow-1: rgba(59,130,246,0.08);
            --bg-glow-2: rgba(14,165,233,0.06);
            --badge-pendiente-bg: #fef3c7; --badge-pendiente-text: #92400e;
            --badge-confirmada-bg: #d1fae5; --badge-confirmada-text: #065f46;
        }
        [data-theme="dark"] {
            --bg-primary: #0a0a14;
            --bg-card: rgba(255,255,255,0.04);
            --bg-card-hover: rgba(255,255,255,0.07);
            --border-card: rgba(255,255,255,0.08);
            --text-primary: #f0f0f5;
            --text-secondary: #8888a0;
            --accent: #d4a853;
            --accent-gradient: linear-gradient(135deg, #d4a853, #b8860b);
            --accent-btn-text: #1a1a2e;
            --green: #34d399; --red: #f87171; --blue: #60a5fa; --purple: #a78bfa; --orange: #fb923c;
            --kpi-shadow: none; --card-shadow: none;
            --card-hover-shadow: 0 12px 40px rgba(0,0,0,0.3);
            --bg-glow-1: rgba(212,168,83,0.06);
            --bg-glow-2: rgba(96,165,250,0.04);
            --badge-pendiente-bg: rgba(251,191,36,0.15); --badge-pendiente-text: #fbbf24;
            --badge-confirmada-bg: rgba(52,211,153,0.15); --badge-confirmada-text: #34d399;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            overflow-x: hidden;
            transition: background 0.4s ease, color 0.4s ease;
        }
        body::before {
            content: '';
            position: fixed; top: -50%; left: -50%;
            width: 200%; height: 200%;
            background: radial-gradient(ellipse at 20% 50%, var(--bg-glow-1) 0%, transparent 50%),
                        radial-gradient(ellipse at 80% 20%, var(--bg-glow-2) 0%, transparent 50%);
            z-index: 0;
            animation: bgShift 20s ease-in-out infinite;
        }
        @keyframes bgShift { 0%,100%{transform:translate(0,0)} 50%{transform:translate(-2%,-1%)} }

        .container { position: relative; z-index: 1; max-width: 1400px; margin: 0 auto; padding: 24px 20px; }

        /* Header */
        .header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 28px; }
        .header-left h1 { font-size: 1.75rem; font-weight: 800; background: var(--accent-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .header-left p { color: var(--text-secondary); font-size: 0.875rem; margin-top: 4px; }
        .header-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .barberia-info { text-align: right; font-size: 0.85rem; color: var(--text-secondary); }
        .barberia-info strong { color: var(--text-primary); display: block; }

        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; font-weight: 600; font-size: 0.875rem; border: none; border-radius: 12px; cursor: pointer; transition: all 0.3s ease; text-decoration: none; }
        .btn-primary { background: var(--accent-gradient); color: var(--accent-btn-text); }
        .btn-danger  { background: var(--red); color: #fff; }
        .btn:hover { transform: translateY(-2px); box-shadow: var(--card-hover-shadow); }

        .theme-toggle { width: 44px; height: 44px; border-radius: 12px; border: 1px solid var(--border-card); background: var(--bg-card); cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; transition: all 0.3s ease; backdrop-filter: blur(10px); color: var(--text-primary); }
        .theme-toggle:hover { transform: translateY(-2px); box-shadow: var(--card-hover-shadow); }

        /* KPIs */
        .kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 28px; }
        .kpi-card { background: var(--bg-card); border: 1px solid var(--border-card); border-radius: 16px; padding: 20px; backdrop-filter: blur(20px); box-shadow: var(--kpi-shadow); transition: all 0.3s ease; animation: fadeUp 0.5s ease forwards; opacity: 0; }
        .kpi-card:hover { background: var(--bg-card-hover); transform: translateY(-4px); box-shadow: var(--card-hover-shadow); }
        .kpi-card:nth-child(1){animation-delay:.05s} .kpi-card:nth-child(2){animation-delay:.1s} .kpi-card:nth-child(3){animation-delay:.15s} .kpi-card:nth-child(4){animation-delay:.2s} .kpi-card:nth-child(5){animation-delay:.25s}
        .kpi-icon { font-size: 1.5rem; margin-bottom: 8px; }
        .kpi-value { font-size: 1.75rem; font-weight: 800; line-height: 1; margin-bottom: 4px; }
        .kpi-label { font-size: 0.72rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 500; }
        .kpi-card.gold .kpi-value{color:var(--accent)} .kpi-card.green .kpi-value{color:var(--green)} .kpi-card.blue .kpi-value{color:var(--blue)} .kpi-card.purple .kpi-value{color:var(--purple)} .kpi-card.red .kpi-value{color:var(--red)}

        /* Period filters */
        .filters { display: flex; gap: 6px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-btn { padding: 7px 18px; border: 1px solid var(--border-card); background: var(--bg-card); color: var(--text-secondary); border-radius: 100px; cursor: pointer; font-family: 'Inter', sans-serif; font-size: 0.8rem; font-weight: 500; transition: all 0.3s ease; backdrop-filter: blur(10px); }
        .filter-btn:hover { border-color: var(--accent); color: var(--accent); }
        .filter-btn.active { background: var(--accent-gradient); color: var(--accent-btn-text); border-color: transparent; font-weight: 600; }

        /* ─── CITAS PENDIENTES PANEL ─────────────────────────────────── */
        .section-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; }
        .section-title .count-badge { background: var(--accent-gradient); color: var(--accent-btn-text); font-size: 0.75rem; padding: 2px 10px; border-radius: 100px; font-weight: 600; }

        .citas-panel { background: var(--bg-card); border: 1px solid var(--border-card); border-radius: 20px; padding: 24px; backdrop-filter: blur(20px); box-shadow: var(--card-shadow); margin-bottom: 28px; animation: fadeUp 0.4s ease forwards; opacity: 0; }

        .citas-table-wrap { overflow-x: auto; }
        table.citas-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        table.citas-table thead th { color: var(--text-secondary); font-weight: 600; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; padding: 10px 14px; text-align: left; border-bottom: 1px solid var(--border-card); }
        table.citas-table tbody tr { transition: background 0.2s ease; }
        table.citas-table tbody tr:hover { background: rgba(37,99,235,0.04); }
        table.citas-table tbody td { padding: 14px 14px; border-bottom: 1px solid var(--border-card); vertical-align: middle; }
        table.citas-table tbody tr:last-child td { border-bottom: none; }

        .cliente-cell strong { display: block; font-weight: 600; }
        .cliente-cell span { font-size: 0.78rem; color: var(--text-secondary); }

        .badge { display: inline-block; padding: 3px 10px; border-radius: 100px; font-size: 0.72rem; font-weight: 600; }
        .badge-pendiente { background: var(--badge-pendiente-bg); color: var(--badge-pendiente-text); }
        .badge-confirmada { background: var(--badge-confirmada-bg); color: var(--badge-confirmada-text); }

        .empty-state { text-align: center; padding: 48px 24px; color: var(--text-secondary); }
        .empty-state .empty-icon { font-size: 3rem; margin-bottom: 12px; display: block; }
        .empty-state p { font-size: 0.9rem; }

        .loading-row td { text-align: center; padding: 32px; color: var(--text-secondary); font-size: 0.875rem; }

        /* Charts */
        .charts-section-title { font-size: 1rem; font-weight: 700; margin-bottom: 16px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.06em; font-size: 0.75rem; }
        .charts-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .chart-card { background: var(--bg-card); border: 1px solid var(--border-card); border-radius: 16px; padding: 24px; backdrop-filter: blur(20px); box-shadow: var(--card-shadow); animation: fadeUp 0.6s ease forwards; opacity: 0; transition: all 0.3s ease; }
        .chart-card:hover { box-shadow: var(--card-hover-shadow); }
        .chart-card:nth-child(1){animation-delay:.1s} .chart-card:nth-child(2){animation-delay:.15s} .chart-card:nth-child(3){animation-delay:.2s}
        .chart-card.wide { grid-column: span 2; }
        .chart-title { font-size: 0.9rem; font-weight: 600; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
        .chart-container { position: relative; width: 100%; height: 260px; }

        @keyframes fadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }

        @media(max-width:900px){ .charts-grid{grid-template-columns:1fr} .chart-card.wide{grid-column:span 1} }
        @media(max-width:600px){ .kpis{grid-template-columns:repeat(2,1fr)} .header{text-align:center;justify-content:center} }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <div class="header-left">
                <h1>💈 Panel de Administración</h1>
                <p id="periodo-label">Cargando estadísticas...</p>
            </div>
            <div class="header-actions">
                <div class="barberia-info">
                    <strong>{{ Auth::user()->barberia->nombre ?? 'Sin Barbería' }}</strong>
                    {{ Auth::user()->name }}
                </div>
                <button class="theme-toggle" id="theme-toggle" title="Cambiar tema" onclick="toggleTheme()">🌙</button>
                <a href="#" id="btn-pdf" class="btn btn-primary" onclick="descargarPdf(event)">📄 Reporte PDF</a>
                <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-danger">🚪 Salir</button>
                </form>
            </div>
        </header>

        <!-- KPIs -->
        <div class="kpis" id="kpis">
            <div class="kpi-card gold"><div class="kpi-icon">💰</div><div class="kpi-value" id="kpi-ingresos">--</div><div class="kpi-label">Ingresos</div></div>
            <div class="kpi-card blue"><div class="kpi-icon">📅</div><div class="kpi-value" id="kpi-citas">--</div><div class="kpi-label">Total Citas</div></div>
            <div class="kpi-card green"><div class="kpi-icon">✅</div><div class="kpi-value" id="kpi-completadas">--</div><div class="kpi-label">Completadas</div></div>
            <div class="kpi-card purple"><div class="kpi-icon">👤</div><div class="kpi-value" id="kpi-clientes">--</div><div class="kpi-label">Clientes Nuevos</div></div>
            <div class="kpi-card red"><div class="kpi-icon">📉</div><div class="kpi-value" id="kpi-cancelacion">--</div><div class="kpi-label">Tasa Cancelación</div></div>
        </div>

        <!-- ── CITAS PENDIENTES (LO PRIMERO Y MÁS IMPORTANTE) ────────── -->
        <div class="citas-panel">
            <div class="section-title">
                🗓️ Citas Próximas
                <span class="count-badge" id="citas-count">0</span>
                <span style="font-size:0.8rem; font-weight:400; color:var(--text-secondary); margin-left:auto;">Actualizando automáticamente cada 60s</span>
            </div>
            <div class="citas-table-wrap">
                <table class="citas-table">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Servicios</th>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Barbero</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="citas-tbody">
                        <tr class="loading-row"><td colspan="6">⏳ Cargando citas...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── GRÁFICAS ───────────────────────────────────────────────── -->
        <div class="filters" id="filtros">
            <button class="filter-btn" data-periodo="1w">1 Semana</button>
            <button class="filter-btn active" data-periodo="1m">1 Mes</button>
            <button class="filter-btn" data-periodo="3m">3 Meses</button>
            <button class="filter-btn" data-periodo="6m">6 Meses</button>
            <button class="filter-btn" data-periodo="1y">1 Año</button>
        </div>

        <div class="charts-grid">
            <div class="chart-card wide">
                <div class="chart-title">📈 Ingresos por Día</div>
                <div class="chart-container"><canvas id="chart-ingresos"></canvas></div>
            </div>
            <div class="chart-card">
                <div class="chart-title">✂️ Servicios Más Solicitados</div>
                <div class="chart-container"><canvas id="chart-servicios"></canvas></div>
            </div>
            <div class="chart-card">
                <div class="chart-title">📊 Estado de Citas</div>
                <div class="chart-container"><canvas id="chart-estados"></canvas></div>
            </div>
        </div>
    </div>

    <script>
    let periodoActual = '1m';
    const charts = {};
    let isDark = false;

    // ─── Theme ───────────────────────────────────────────────────
    function toggleTheme() {
        isDark = !isDark;
        document.documentElement.setAttribute('data-theme', isDark ? 'dark' : '');
        document.getElementById('theme-toggle').textContent = isDark ? '☀️' : '🌙';
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        applyChartTheme();
        cargarDatos(periodoActual);
    }
    function loadSavedTheme() {
        if (localStorage.getItem('theme') === 'dark') {
            isDark = true;
            document.documentElement.setAttribute('data-theme', 'dark');
            document.getElementById('theme-toggle').textContent = '☀️';
        }
    }
    function applyChartTheme() {
        Chart.defaults.color = isDark ? '#8888a0' : '#64748b';
        Chart.defaults.borderColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
    }
    function getGridColor() { return isDark ? 'rgba(255,255,255,0.04)' : 'rgba(0,0,0,0.06)'; }
    function getAccentColor() { return isDark ? '#d4a853' : '#2563eb'; }
    function getAccentRgba(a) { return isDark ? `rgba(212,168,83,${a})` : `rgba(37,99,235,${a})`; }
    function C() {
        return isDark
            ? {gold:'#d4a853',green:'#34d399',red:'#f87171',blue:'#60a5fa',purple:'#a78bfa',orange:'#fb923c',cyan:'#22d3ee',pink:'#f472b6'}
            : {gold:'#2563eb',green:'#059669',red:'#dc2626',blue:'#3b82f6',purple:'#7c3aed',orange:'#ea580c',cyan:'#0891b2',pink:'#db2777'};
    }
    function getPalette() {
        const c = C();
        return [c.gold, c.blue, c.green, c.purple, c.orange, c.cyan, c.pink, c.red];
    }

    // ─── Init ────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        loadSavedTheme();
        applyChartTheme();
        initFiltros();
        cargarCitasPendientes();
        cargarDatos(periodoActual);
        // Refrescar citas cada 60 segundos
        setInterval(cargarCitasPendientes, 60000);
    });

    function initFiltros() {
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                periodoActual = btn.dataset.periodo;
                cargarDatos(periodoActual);
            });
        });
    }

    // ─── CITAS PENDIENTES ────────────────────────────────────────
    async function cargarCitasPendientes() {
        try {
            const res = await fetch('/admin/citas-pendientes');
            const citas = await res.json();
            document.getElementById('citas-count').textContent = citas.length;
            const tbody = document.getElementById('citas-tbody');

            if (citas.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <span class="empty-icon">🎉</span>
                                <p>¡No hay citas pendientes por ahora!</p>
                            </div>
                        </td>
                    </tr>`;
                return;
            }

            tbody.innerHTML = citas.map(c => `
                <tr>
                    <td class="cliente-cell">
                        <strong>${escHtml(c.cliente)}</strong>
                        <span>${escHtml(c.telefono)}</span>
                    </td>
                    <td>${escHtml(c.servicios)}</td>
                    <td>${escHtml(c.fecha)}</td>
                    <td><strong>${escHtml(c.hora)}</strong></td>
                    <td>${escHtml(c.barbero)}</td>
                    <td><span class="badge badge-${c.estado}">${c.estado === 'pendiente' ? '⏳ Pendiente' : '✅ Confirmada'}</span></td>
                    <td>
                        <button class="btn btn-primary" style="padding: 4px 8px; font-size: 0.8rem;" onclick="completarCita(${c.id})">✔️ Cobrar</button>
                    </td>
                </tr>
            `).join('');
        } catch(e) {
            document.getElementById('citas-tbody').innerHTML = `<tr class="loading-row"><td colspan="6">⚠️ Error al cargar citas. Recarga la página.</td></tr>`;
            console.error(e);
        }
    }

    function escHtml(str) {
        return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    async function completarCita(id) {
        if (!confirm('¿Marcar esta cita como completada (y cobrarla)? Se enviará un mensaje al cliente pidiendo su calificación.')) return;
        
        try {
            const res = await fetch(`/admin/citas/${id}/completar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            if (res.ok) {
                cargarCitasPendientes();
                cargarDatos(periodoActual);
            } else {
                alert('Error al completar la cita');
            }
        } catch (e) {
            console.error(e);
            alert('Error de conexión');
        }
    }

    // ─── Dashboard KPIs & Charts ────────────────────────────────
    async function cargarDatos(periodo) {
        document.getElementById('periodo-label').textContent = 'Actualizando estadísticas...';
        try {
            const res = await fetch(`/admin/datos?periodo=${periodo}`);
            const data = await res.json();
            actualizarKpis(data.kpis);
            actualizarGraficas(data);
            const t = {'1w':'Última semana','1m':'Último mes','3m':'Últimos 3 meses','6m':'Últimos 6 meses','1y':'Último año'};
            document.getElementById('periodo-label').textContent = `${t[periodo]} — ${data.fecha_inicio} a ${data.fecha_fin}`;
        } catch(e) {
            document.getElementById('periodo-label').textContent = 'Error al cargar estadísticas';
        }
    }

    function actualizarKpis(kpis) {
        animarNumero('kpi-ingresos', kpis.ingresos, '$', '');
        animarNumero('kpi-citas', kpis.total_citas);
        animarNumero('kpi-completadas', kpis.completadas);
        animarNumero('kpi-clientes', kpis.clientes_nuevos);
        animarNumero('kpi-cancelacion', kpis.tasa_cancelacion, '', '%');
    }

    function animarNumero(id, target, prefix='', suffix='') {
        const el = document.getElementById(id);
        const duration = 800;
        const start = performance.now();
        function update(now) {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            const cur = Math.round(target * eased);
            el.textContent = prefix === '$' ? `$${cur.toLocaleString('es-MX')}` : `${prefix}${cur.toLocaleString('es-MX')}${suffix}`;
            if (progress < 1) requestAnimationFrame(update);
        }
        requestAnimationFrame(update);
    }

    function actualizarGraficas(data) {
        crearGraficaIngresos(data.ingresos_por_dia);
        crearGraficaServicios(data.servicios_populares);
        crearGraficaEstados(data.estados_citas);
    }

    function destruirChart(n) { if (charts[n]) { charts[n].destroy(); delete charts[n]; } }

    function crearGraficaIngresos(d) {
        destruirChart('ingresos');
        const ctx = document.getElementById('chart-ingresos').getContext('2d');
        const g = ctx.createLinearGradient(0, 0, 0, 260);
        g.addColorStop(0, getAccentRgba(0.3));
        g.addColorStop(1, getAccentRgba(0));
        charts.ingresos = new Chart(ctx, {
            type: 'line',
            data: { labels: d.labels, datasets: [{ label: 'Ingresos ($)', data: d.data, borderColor: getAccentColor(), backgroundColor: g, fill: true, tension: 0.4, pointRadius: d.data.length > 30 ? 0 : 4, pointBackgroundColor: getAccentColor(), borderWidth: 2 }] },
            options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true,grid:{color:getGridColor()}},x:{grid:{display:false},ticks:{maxTicksLimit:12}}} }
        });
    }

    function crearGraficaServicios(d) {
        destruirChart('servicios');
        if (!d.labels.length) return;
        charts.servicios = new Chart(document.getElementById('chart-servicios'), {
            type: 'doughnut',
            data: { labels: d.labels, datasets: [{ data: d.data, backgroundColor: getPalette().slice(0, d.labels.length), borderWidth: 0, hoverOffset: 8 }] },
            options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'right',labels:{padding:12,font:{size:11}}}}, cutout:'60%' }
        });
    }

    function crearGraficaEstados(d) {
        destruirChart('estados');
        if (!d.labels.length) return;
        const c = C();
        const cm = {'Pendiente':c.orange,'Confirmada':c.blue,'Completada':c.green,'Cancelada':c.red,'No asistió':c.purple};
        charts.estados = new Chart(document.getElementById('chart-estados'), {
            type: 'doughnut',
            data: { labels: d.labels, datasets: [{ data: d.data, backgroundColor: d.labels.map(l => cm[l] || c.cyan), borderWidth: 0, hoverOffset: 8 }] },
            options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'right',labels:{padding:12,font:{size:11}}}}, cutout:'60%' }
        });
    }

    function descargarPdf(e) {
        e.preventDefault();
        window.location.href = `/admin/reporte-pdf?periodo=${periodoActual}`;
    }
    </script>
</body>
</html>
