<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración — Barbería</title>
    <meta name="description" content="Panel de administración con gráficas y reportes para tu barbería">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        /* ─── Light Theme (default) ─────────────────────────────── */
        :root {
            --bg-primary: #f0f4fa;
            --bg-secondary: #e4ecf7;
            --bg-card: rgba(255,255,255,0.85);
            --bg-card-hover: rgba(255,255,255,0.95);
            --border-card: rgba(100,140,200,0.15);
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --accent: #2563eb;
            --accent-light: #3b82f6;
            --accent-gradient: linear-gradient(135deg, #3b82f6, #1d4ed8);
            --accent-btn-text: #ffffff;
            --green: #059669;
            --red: #dc2626;
            --blue: #2563eb;
            --purple: #7c3aed;
            --orange: #ea580c;
            --cyan: #0891b2;
            --kpi-shadow: 0 4px 20px rgba(37,99,235,0.08);
            --card-shadow: 0 2px 12px rgba(0,0,0,0.04);
            --card-hover-shadow: 0 12px 40px rgba(37,99,235,0.12);
            --grid-line: rgba(0,0,0,0.06);
            --bg-glow-1: rgba(59,130,246,0.08);
            --bg-glow-2: rgba(14,165,233,0.06);
            --bg-glow-3: rgba(99,102,241,0.05);
            --filter-active-text: #ffffff;
            --chart-text: #64748b;
            --chart-border: rgba(0,0,0,0.06);
        }

        /* ─── Dark Theme ────────────────────────────────────────── */
        [data-theme="dark"] {
            --bg-primary: #0a0a14;
            --bg-secondary: #12121f;
            --bg-card: rgba(255,255,255,0.04);
            --bg-card-hover: rgba(255,255,255,0.07);
            --border-card: rgba(255,255,255,0.08);
            --text-primary: #f0f0f5;
            --text-secondary: #8888a0;
            --accent: #d4a853;
            --accent-light: #f0d48a;
            --accent-gradient: linear-gradient(135deg, #d4a853, #b8860b);
            --accent-btn-text: #1a1a2e;
            --green: #34d399;
            --red: #f87171;
            --blue: #60a5fa;
            --purple: #a78bfa;
            --orange: #fb923c;
            --cyan: #22d3ee;
            --kpi-shadow: none;
            --card-shadow: none;
            --card-hover-shadow: 0 12px 40px rgba(0,0,0,0.3);
            --grid-line: rgba(255,255,255,0.04);
            --bg-glow-1: rgba(212,168,83,0.06);
            --bg-glow-2: rgba(96,165,250,0.04);
            --bg-glow-3: rgba(167,139,250,0.04);
            --filter-active-text: #1a1a2e;
            --chart-text: #8888a0;
            --chart-border: rgba(255,255,255,0.06);
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
                        radial-gradient(ellipse at 80% 20%, var(--bg-glow-2) 0%, transparent 50%),
                        radial-gradient(ellipse at 50% 80%, var(--bg-glow-3) 0%, transparent 50%);
            z-index: 0;
            animation: bgShift 20s ease-in-out infinite;
            transition: background 0.4s ease;
        }

        @keyframes bgShift {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(-2%, -1%); }
        }

        .container {
            position: relative; z-index: 1;
            max-width: 1400px; margin: 0 auto; padding: 24px 20px;
        }

        /* Header */
        .header {
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 16px; margin-bottom: 32px;
        }

        .header-left h1 {
            font-size: 1.75rem; font-weight: 800;
            background: var(--accent-gradient);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }

        .header-left p { color: var(--text-secondary); font-size: 0.875rem; margin-top: 4px; }

        .header-actions { display: flex; align-items: center; gap: 12px; }

        /* Theme toggle */
        .theme-toggle {
            width: 44px; height: 44px;
            border-radius: 12px;
            border: 1px solid var(--border-card);
            background: var(--bg-card);
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            color: var(--text-primary);
        }

        .theme-toggle:hover {
            transform: translateY(-2px);
            box-shadow: var(--card-hover-shadow);
            border-color: var(--accent);
        }

        .btn-pdf {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 24px;
            background: var(--accent-gradient);
            color: var(--accent-btn-text);
            font-weight: 600; font-size: 0.875rem;
            border: none; border-radius: 12px;
            cursor: pointer; transition: all 0.3s ease; text-decoration: none;
        }

        .btn-pdf:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(37,99,235,0.25);
        }

        [data-theme="dark"] .btn-pdf:hover {
            box-shadow: 0 8px 24px rgba(212,168,83,0.3);
        }

        /* Period filters */
        .filters { display: flex; gap: 6px; margin-bottom: 28px; flex-wrap: wrap; }

        .filter-btn {
            padding: 8px 20px;
            border: 1px solid var(--border-card);
            background: var(--bg-card);
            color: var(--text-secondary);
            border-radius: 100px; cursor: pointer;
            font-family: 'Inter', sans-serif; font-size: 0.8rem; font-weight: 500;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .filter-btn:hover { border-color: var(--accent); color: var(--accent); }

        .filter-btn.active {
            background: var(--accent-gradient);
            color: var(--filter-active-text);
            border-color: transparent; font-weight: 600;
        }

        /* KPI Cards */
        .kpis {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px; margin-bottom: 32px;
        }

        .kpi-card {
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            border-radius: 16px; padding: 20px;
            backdrop-filter: blur(20px);
            box-shadow: var(--kpi-shadow);
            transition: all 0.3s ease;
            animation: fadeInUp 0.5s ease forwards; opacity: 0;
        }

        .kpi-card:hover {
            background: var(--bg-card-hover);
            transform: translateY(-4px);
            box-shadow: var(--card-hover-shadow);
        }

        .kpi-card:nth-child(1) { animation-delay: 0.05s; }
        .kpi-card:nth-child(2) { animation-delay: 0.1s; }
        .kpi-card:nth-child(3) { animation-delay: 0.15s; }
        .kpi-card:nth-child(4) { animation-delay: 0.2s; }
        .kpi-card:nth-child(5) { animation-delay: 0.25s; }

        .kpi-icon { font-size: 1.5rem; margin-bottom: 8px; }
        .kpi-value { font-size: 1.75rem; font-weight: 800; line-height: 1; margin-bottom: 4px; }
        .kpi-label {
            font-size: 0.75rem; color: var(--text-secondary);
            text-transform: uppercase; letter-spacing: 0.05em; font-weight: 500;
        }

        .kpi-card.gold .kpi-value { color: var(--accent); }
        .kpi-card.green .kpi-value { color: var(--green); }
        .kpi-card.blue .kpi-value { color: var(--blue); }
        .kpi-card.purple .kpi-value { color: var(--purple); }
        .kpi-card.red .kpi-value { color: var(--red); }

        /* Charts Grid */
        .charts-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }

        .chart-card {
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            border-radius: 16px; padding: 24px;
            backdrop-filter: blur(20px);
            box-shadow: var(--card-shadow);
            animation: fadeInUp 0.6s ease forwards; opacity: 0;
            transition: all 0.3s ease;
        }

        .chart-card:hover { box-shadow: var(--card-hover-shadow); }

        .chart-card:nth-child(1) { animation-delay: 0.1s; }
        .chart-card:nth-child(2) { animation-delay: 0.15s; }
        .chart-card:nth-child(3) { animation-delay: 0.2s; }
        .chart-card:nth-child(4) { animation-delay: 0.25s; }
        .chart-card:nth-child(5) { animation-delay: 0.3s; }
        .chart-card:nth-child(6) { animation-delay: 0.35s; }

        .chart-card.wide { grid-column: span 2; }

        .chart-title {
            font-size: 0.95rem; font-weight: 600; margin-bottom: 16px;
            display: flex; align-items: center; gap: 8px;
        }

        .chart-container { position: relative; width: 100%; height: 280px; }

        @keyframes spin { to { transform: rotate(360deg); } }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsive */
        @media (max-width: 900px) {
            .charts-grid { grid-template-columns: 1fr; }
            .chart-card.wide { grid-column: span 1; }
        }
        @media (max-width: 600px) {
            .kpis { grid-template-columns: repeat(2, 1fr); }
            .header { text-align: center; justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="header-left">
                <h1>💈 Panel de Administración</h1>
                <p id="periodo-label">Cargando datos...</p>
            </div>
            <div class="header-actions">
                <button class="theme-toggle" id="theme-toggle" title="Cambiar tema" onclick="toggleTheme()">🌙</button>
                <a href="#" id="btn-pdf" class="btn-pdf" onclick="descargarPdf(event)">
                    📄 Descargar Reporte PDF
                </a>
            </div>
        </header>

        <div class="filters" id="filtros">
            <button class="filter-btn" data-periodo="1w">1 Semana</button>
            <button class="filter-btn active" data-periodo="1m">1 Mes</button>
            <button class="filter-btn" data-periodo="3m">3 Meses</button>
            <button class="filter-btn" data-periodo="6m">6 Meses</button>
            <button class="filter-btn" data-periodo="1y">1 Año</button>
        </div>

        <div class="kpis" id="kpis">
            <div class="kpi-card gold">
                <div class="kpi-icon">💰</div>
                <div class="kpi-value" id="kpi-ingresos">--</div>
                <div class="kpi-label">Ingresos</div>
            </div>
            <div class="kpi-card blue">
                <div class="kpi-icon">📅</div>
                <div class="kpi-value" id="kpi-citas">--</div>
                <div class="kpi-label">Total Citas</div>
            </div>
            <div class="kpi-card green">
                <div class="kpi-icon">✅</div>
                <div class="kpi-value" id="kpi-completadas">--</div>
                <div class="kpi-label">Completadas</div>
            </div>
            <div class="kpi-card purple">
                <div class="kpi-icon">👤</div>
                <div class="kpi-value" id="kpi-clientes">--</div>
                <div class="kpi-label">Clientes Nuevos</div>
            </div>
            <div class="kpi-card red">
                <div class="kpi-icon">📉</div>
                <div class="kpi-value" id="kpi-cancelacion">--</div>
                <div class="kpi-label">Tasa Cancelación</div>
            </div>
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
            <div class="chart-card">
                <div class="chart-title">👥 Clientes Nuevos vs Recurrentes</div>
                <div class="chart-container"><canvas id="chart-clientes"></canvas></div>
            </div>
            <div class="chart-card">
                <div class="chart-title">⏰ Horas Pico</div>
                <div class="chart-container"><canvas id="chart-horas"></canvas></div>
            </div>
            <div class="chart-card wide">
                <div class="chart-title">📆 Tendencia de Citas</div>
                <div class="chart-container"><canvas id="chart-tendencia"></canvas></div>
            </div>
        </div>
    </div>

    <script>
    let periodoActual = '1m';
    const charts = {};
    let isDark = false;

    // ─── Theme Toggle ───────────────────────────────────────────────
    function toggleTheme() {
        isDark = !isDark;
        document.documentElement.setAttribute('data-theme', isDark ? 'dark' : '');
        document.getElementById('theme-toggle').textContent = isDark ? '☀️' : '🌙';
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        applyChartTheme();
        cargarDatos(periodoActual);
    }

    function loadSavedTheme() {
        const saved = localStorage.getItem('theme');
        if (saved === 'dark') {
            isDark = true;
            document.documentElement.setAttribute('data-theme', 'dark');
            document.getElementById('theme-toggle').textContent = '☀️';
        }
    }

    function applyChartTheme() {
        const textColor = isDark ? '#8888a0' : '#64748b';
        const borderColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
        Chart.defaults.color = textColor;
        Chart.defaults.borderColor = borderColor;
    }

    function getGridColor() {
        return isDark ? 'rgba(255,255,255,0.04)' : 'rgba(0,0,0,0.06)';
    }

    function getAccentColor() { return isDark ? '#d4a853' : '#2563eb'; }
    function getAccentRgba(a) { return isDark ? `rgba(212,168,83,${a})` : `rgba(37,99,235,${a})`; }

    // ─── Chart Colors ───────────────────────────────────────────────
    function C() {
        return isDark
            ? { gold:'#d4a853', green:'#34d399', red:'#f87171', blue:'#60a5fa', purple:'#a78bfa', orange:'#fb923c', cyan:'#22d3ee', pink:'#f472b6', lime:'#a3e635', goldLight:'#f0d48a' }
            : { gold:'#2563eb', green:'#059669', red:'#dc2626', blue:'#3b82f6', purple:'#7c3aed', orange:'#ea580c', cyan:'#0891b2', pink:'#db2777', lime:'#65a30d', goldLight:'#60a5fa' };
    }

    function getPalette() {
        const c = C();
        return [c.gold, c.blue, c.green, c.purple, c.orange, c.cyan, c.pink, c.red, c.lime, c.goldLight];
    }

    // ─── Init ───────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        loadSavedTheme();
        applyChartTheme();
        initFiltros();
        cargarDatos(periodoActual);
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

    async function cargarDatos(periodo) {
        document.getElementById('periodo-label').textContent = 'Cargando datos...';
        try {
            const res = await fetch(`/admin/datos?periodo=${periodo}`);
            const data = await res.json();
            actualizarKpis(data.kpis);
            actualizarGraficas(data);
            const t = { '1w':'Última semana','1m':'Último mes','3m':'Últimos 3 meses','6m':'Últimos 6 meses','1y':'Último año' };
            document.getElementById('periodo-label').textContent = `${t[periodo]} — ${data.fecha_inicio} a ${data.fecha_fin}`;
        } catch (e) {
            document.getElementById('periodo-label').textContent = 'Error al cargar datos';
            console.error(e);
        }
    }

    // ─── KPIs ───────────────────────────────────────────────────────
    function actualizarKpis(kpis) {
        animarNumero('kpi-ingresos', kpis.ingresos, '$', '');
        animarNumero('kpi-citas', kpis.total_citas);
        animarNumero('kpi-completadas', kpis.completadas);
        animarNumero('kpi-clientes', kpis.clientes_nuevos);
        animarNumero('kpi-cancelacion', kpis.tasa_cancelacion, '', '%');
    }

    function animarNumero(id, target, prefix = '', suffix = '') {
        const el = document.getElementById(id);
        const duration = 800;
        const startTime = performance.now();
        function update(currentTime) {
            const progress = Math.min((currentTime - startTime) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            const current = Math.round(target * eased);
            el.textContent = prefix === '$'
                ? `$${current.toLocaleString('es-MX')}`
                : `${prefix}${current.toLocaleString('es-MX')}${suffix}`;
            if (progress < 1) requestAnimationFrame(update);
        }
        requestAnimationFrame(update);
    }

    // ─── Charts ─────────────────────────────────────────────────────
    function actualizarGraficas(data) {
        crearGraficaIngresos(data.ingresos_por_dia);
        crearGraficaServicios(data.servicios_populares);
        crearGraficaEstados(data.estados_citas);
        crearGraficaClientes(data.clientes_nuevos_vs_recurrentes);
        crearGraficaHoras(data.horas_pico);
        crearGraficaTendencia(data.tendencia_citas);
    }

    function destruirChart(n) { if (charts[n]) charts[n].destroy(); }

    function crearGraficaIngresos(d) {
        destruirChart('ingresos');
        const ctx = document.getElementById('chart-ingresos').getContext('2d');
        const g = ctx.createLinearGradient(0, 0, 0, 280);
        g.addColorStop(0, getAccentRgba(0.3));
        g.addColorStop(1, getAccentRgba(0));
        charts.ingresos = new Chart(ctx, {
            type: 'line',
            data: { labels: d.labels, datasets: [{ label: 'Ingresos ($)', data: d.data, borderColor: getAccentColor(), backgroundColor: g, fill: true, tension: 0.4, pointRadius: d.data.length > 30 ? 0 : 4, pointBackgroundColor: getAccentColor(), borderWidth: 2 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: getGridColor() } }, x: { grid: { display: false }, ticks: { maxTicksLimit: 12 } } } }
        });
    }

    function crearGraficaServicios(d) {
        destruirChart('servicios');
        charts.servicios = new Chart(document.getElementById('chart-servicios'), {
            type: 'doughnut',
            data: { labels: d.labels, datasets: [{ data: d.data, backgroundColor: getPalette().slice(0, d.labels.length), borderWidth: 0, hoverOffset: 8 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { padding: 12, font: { size: 11 } } } }, cutout: '60%' }
        });
    }

    function crearGraficaEstados(d) {
        destruirChart('estados');
        const c = C();
        const cm = { 'Pendiente': c.orange, 'Confirmada': c.blue, 'Completada': c.green, 'Cancelada': c.red, 'No asistió': c.purple };
        charts.estados = new Chart(document.getElementById('chart-estados'), {
            type: 'doughnut',
            data: { labels: d.labels, datasets: [{ data: d.data, backgroundColor: d.labels.map(l => cm[l] || c.cyan), borderWidth: 0, hoverOffset: 8 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { padding: 12, font: { size: 11 } } } }, cutout: '60%' }
        });
    }

    function crearGraficaClientes(d) {
        destruirChart('clientes');
        const c = C();
        charts.clientes = new Chart(document.getElementById('chart-clientes'), {
            type: 'bar',
            data: { labels: d.labels, datasets: [
                { label: 'Nuevos', data: d.nuevos, backgroundColor: c.cyan, borderRadius: 6 },
                { label: 'Recurrentes', data: d.recurrentes, backgroundColor: c.purple, borderRadius: 6 }
            ] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { font: { size: 11 } } } }, scales: { y: { beginAtZero: true, grid: { color: getGridColor() } }, x: { grid: { display: false }, ticks: { maxTicksLimit: 10 } } } }
        });
    }

    function crearGraficaHoras(d) {
        destruirChart('horas');
        const accent = getAccentColor();
        charts.horas = new Chart(document.getElementById('chart-horas'), {
            type: 'bar',
            data: { labels: d.labels, datasets: [{ label: 'Citas', data: d.data, backgroundColor: d.data.map((v, i, a) => v === Math.max(...a) ? accent : getAccentRgba(0.3)), borderRadius: 6 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: getGridColor() } }, x: { grid: { display: false } } } }
        });
    }

    function crearGraficaTendencia(d) {
        destruirChart('tendencia');
        const ctx = document.getElementById('chart-tendencia').getContext('2d');
        const c = C();
        const g = ctx.createLinearGradient(0, 0, 0, 280);
        g.addColorStop(0, isDark ? 'rgba(96,165,250,0.25)' : 'rgba(59,130,246,0.15)');
        g.addColorStop(1, isDark ? 'rgba(96,165,250,0)' : 'rgba(59,130,246,0)');
        charts.tendencia = new Chart(ctx, {
            type: 'line',
            data: { labels: d.labels, datasets: [{ label: 'Citas', data: d.data, borderColor: c.blue, backgroundColor: g, fill: true, tension: 0.4, pointRadius: d.data.length > 30 ? 0 : 4, pointBackgroundColor: c.blue, borderWidth: 2 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: getGridColor() } }, x: { grid: { display: false }, ticks: { maxTicksLimit: 12 } } } }
        });
    }

    // ─── PDF ────────────────────────────────────────────────────────
    function descargarPdf(e) {
        e.preventDefault();
        window.location.href = `/admin/reporte-pdf?periodo=${periodoActual}`;
    }
    </script>
</body>
</html>
