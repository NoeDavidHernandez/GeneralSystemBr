@extends('layouts.admin')

@section('title', 'Dashboard')

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js" defer></script>
@endpush

@push('styles')
<style>
    .kpis {
        display: flex;
        flex-direction: row;
        flex-wrap: nowrap;
        gap: 12px;
        margin-bottom: 28px;
        overflow-x: auto;
        padding-bottom: 4px;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }
    .kpis::-webkit-scrollbar { display: none; }
    .kpi-card {
        flex: 1 1 0;
        min-width: 130px;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 16px 18px;
        box-shadow: var(--shadow-sm);
        transition: all 0.3s ease;
        animation: fadeUp 0.5s ease forwards;
        opacity: 0;
    }
    .kpi-card:hover { background: var(--bg-card-hover); transform: translateY(-4px); box-shadow: var(--shadow-md); }
    .kpi-card:nth-child(1){animation-delay:.05s} .kpi-card:nth-child(2){animation-delay:.1s} .kpi-card:nth-child(3){animation-delay:.15s} .kpi-card:nth-child(4){animation-delay:.2s} .kpi-card:nth-child(5){animation-delay:.25s}
    .kpi-icon { font-size: 1.4rem; margin-bottom: 8px; }
    .kpi-value { font-size: 1.65rem; font-weight: 800; line-height: 1; margin-bottom: 4px; color: var(--text-primary); }
    .kpi-label { font-size: 0.7rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; }
    .kpi-card.gold .kpi-icon{color:var(--accent)} .kpi-card.green .kpi-icon{color:var(--green)} .kpi-card.blue .kpi-icon{color:var(--blue)} .kpi-card.purple .kpi-icon{color:var(--purple)} .kpi-card.red .kpi-icon{color:var(--red)}

    .filters { display: flex; gap: 6px; margin-bottom: 20px; flex-wrap: wrap; }
    .filter-btn { padding: 7px 18px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-secondary); border-radius: 100px; cursor: pointer; font-family: 'Inter', sans-serif; font-size: 0.8rem; font-weight: 500; transition: all 0.3s ease; }
    .filter-btn:hover { border-color: var(--accent); color: var(--accent); }
    .filter-btn.active { background: var(--accent-gradient); color: var(--accent-btn-text); border-color: transparent; font-weight: 600; }

    .section-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; color: var(--text-primary); }
    .section-title .count-badge { background: var(--accent-gradient); color: var(--accent-btn-text); font-size: 0.75rem; padding: 2px 10px; border-radius: 100px; font-weight: 600; }
    .citas-panel { margin-bottom: 28px; animation: fadeUp 0.4s ease forwards; opacity: 0; }
    .citas-table-wrap { overflow-x: auto; }
    table.citas-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
    table.citas-table thead th { color: var(--text-secondary); font-weight: 600; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; padding: 12px 14px; text-align: left; border-bottom: 1px solid var(--border-color); }
    table.citas-table tbody tr { transition: background 0.2s ease; }
    table.citas-table tbody tr:hover { background: var(--sidebar-active-bg); }
    table.citas-table tbody td { padding: 14px 14px; border-bottom: 1px solid var(--border-color); vertical-align: middle; color: var(--text-primary); }
    table.citas-table tbody tr:last-child td { border-bottom: none; }
    .cliente-cell strong { display: block; font-weight: 600; color: var(--text-primary); }
    .cliente-cell span { font-size: 0.78rem; color: var(--text-secondary); }
    .badge { display: inline-block; padding: 4px 10px; border-radius: 100px; font-size: 0.72rem; font-weight: 600; }
    .badge-pendiente { background: rgba(245, 158, 11, 0.1); color: #d97706; }
    .badge-confirmada { background: rgba(16, 185, 129, 0.1); color: #059669; }
    .empty-state { text-align: center; padding: 48px 24px; color: var(--text-secondary); }
    .empty-state .empty-icon { font-size: 3rem; margin-bottom: 12px; display: block; }

    .charts-row { display: grid; gap: 20px; margin-bottom: 20px; }
    .charts-row.top { grid-template-columns: 2fr 1fr; }
    .charts-row.bottom { grid-template-columns: 1fr 1fr; }
    .chart-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 24px; box-shadow: var(--shadow-sm); animation: fadeUp 0.6s ease forwards; opacity: 0; transition: all 0.3s ease; }
    .chart-card:hover { box-shadow: var(--shadow-md); }
    .chart-title { font-size: 0.95rem; font-weight: 600; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; color: var(--text-primary); }
    .chart-container { position: relative; width: 100%; height: 260px; }

    @keyframes fadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
    @media(max-width:1200px){ .charts-row.top{grid-template-columns:1fr} }
    @media(max-width:900px){ .charts-row.bottom{grid-template-columns:1fr} }

</style>
@endpush

@section('content')
<div class="page-header">
    <div class="page-title">
        <h1>Buen día, {{ explode(' ', Auth::user()->name)[0] ?? 'Admin' }} </h1>
        <p id="periodo-label">Resumen de actividad de {{ Auth::user()->barberia->nombre ?? 'tu negocio' }}</p>
    </div>
    <div class="page-actions" style="display:flex;gap:12px;">
        @if(Auth::user()->rol === 'admin')
        <button class="btn btn-primary" onclick="abrirModalLocal()"><i data-lucide="plus"></i> Nuevo Servicio</button>
        <a href="#" class="btn" style="background:var(--bg-card);border:1px solid var(--border-color);color:var(--text-primary);" onclick="descargarPdf(event)"><i data-lucide="download"></i> Exportar</a>
        @endif
    </div>
</div>

@if(Auth::user()->rol === 'admin')
<div class="kpis" id="kpis">
    <div class="kpi-card gold"><div class="kpi-icon"><i data-lucide='dollar-sign'></i></div><div class="kpi-value" id="kpi-ingresos">--</div><div class="kpi-label">Ingresos del Mes</div></div>
    <div class="kpi-card blue"><div class="kpi-icon"><i data-lucide='calendar'></i></div><div class="kpi-value" id="kpi-citas">--</div><div class="kpi-label">Citas Totales</div></div>
    <div class="kpi-card green"><div class="kpi-icon"><i data-lucide='check-circle'></i></div><div class="kpi-value" id="kpi-completadas">--</div><div class="kpi-label">Completadas</div></div>
    <div class="kpi-card purple"><div class="kpi-icon"><i data-lucide='users'></i></div><div class="kpi-value" id="kpi-clientes">--</div><div class="kpi-label">Clientes Nuevos</div></div>
    <div class="kpi-card red"><div class="kpi-icon"><i data-lucide='trending-down'></i></div><div class="kpi-value" id="kpi-cancelacion">--</div><div class="kpi-label">Tasa Cancelación</div></div>
</div>
@endif

@if(isset($chatsPausados) && $chatsPausados->count() > 0)
<div class="card citas-panel" style="border:1px solid var(--gold); background:rgba(212,168,83,0.05); margin-bottom: 28px;">
    <div class="section-title" style="color:var(--gold);">
        <i data-lucide="message-square-warning" style="margin-right:8px;"></i> Clientes en espera de un asesor
        <span class="count-badge" style="background:var(--gold);color:#000;">{{ $chatsPausados->count() }}</span>
    </div>
    <div class="citas-table-wrap">
        <table class="citas-table">
            <thead>
                <tr><th>Cliente</th><th>Teléfono</th><th>Hora de Solicitud</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                @foreach($chatsPausados as $cliente)
                <tr>
                    <td><strong>{{ $cliente->nombre }}</strong></td>
                    <td>{{ $cliente->telefono }}</td>
                    <td>{{ $cliente->convEstado->updated_at->diffForHumans() }}</td>
                    <td>
                        <form action="{{ route('admin.chats.reactivar', $cliente->telefono) }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn" style="background:var(--bg-card);border:1px solid var(--border-color);color:var(--text-primary);padding:6px 12px;font-size:0.8rem;">
                                <i data-lucide="bot"></i> Reactivar Bot
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="card citas-panel">
    <div class="section-title">
        Próximas citas hoy
        <span class="count-badge" id="citas-count">0</span>
        <span style="font-size:0.8rem;font-weight:400;color:var(--text-secondary);margin-left:auto;"><i data-lucide="refresh-cw" style="width:14px;height:14px;display:inline-block;vertical-align:middle;margin-right:4px;"></i> Actualizando cada 60s</span>
    </div>
    <div class="citas-table-wrap">
        <table class="citas-table">
            <thead>
                <tr><th>Cliente</th><th>Servicios</th><th>Fecha</th><th>Hora</th><th>Especialista</th><th>Estado</th><th>Acciones</th></tr>
            </thead>
            <tbody id="citas-tbody">
                <tr><td colspan="7" style="text-align:center;padding:32px;"><i data-lucide='loader'></i> Cargando citas...</td></tr>
            </tbody>
        </table>
    </div>
</div>

@if(Auth::user()->rol === 'admin')
<div class="filters" id="filtros">
    <button class="filter-btn" data-periodo="1w">1 Semana</button>
    <button class="filter-btn active" data-periodo="1m">Este Mes</button>
    <button class="filter-btn" data-periodo="3m">3 Meses</button>
    <button class="filter-btn" data-periodo="6m">6 Meses</button>
    <button class="filter-btn" data-periodo="1y">1 Año</button>
</div>

<div class="charts-row top">
    <div class="chart-card">
        <div class="chart-title"><i data-lucide='trending-up'></i> Ingresos por Día</div>
        <div class="chart-container"><canvas id="chart-ingresos"></canvas></div>
    </div>
    <div class="chart-card">
        <div class="chart-title"><i data-lucide="calendar-check" style="color:var(--green);"></i> Servicios Hoy</div>
        <div class="chart-container" id="container-servicios-hoy">
            <canvas id="chart-servicios-hoy"></canvas>
            <div id="empty-servicios-hoy" style="display:none; flex-direction:column; align-items:center; justify-content:center; height:100%; color:var(--text-secondary); font-size:0.9rem;">
                <i data-lucide="coffee" style="margin-bottom:8px; width:32px; height:32px; opacity:0.5;"></i> Aún no hay servicios hoy
            </div>
        </div>
    </div>
</div>

<div class="charts-row bottom">
    <div class="chart-card">
        <div class="chart-title"><i data-lucide="scissors"></i> Top Servicios del Mes</div>
        <div class="chart-container"><canvas id="chart-servicios"></canvas></div>
    </div>
    <div class="chart-card">
        <div class="chart-title"><i data-lucide="dollar-sign" style="color:var(--gold);"></i> Ingresos Hoy (Por Especialista)</div>
        <div class="chart-container" id="container-ingresos-hoy">
            <canvas id="chart-ingresos-hoy"></canvas>
            <div id="empty-ingresos-hoy" style="display:none; flex-direction:column; align-items:center; justify-content:center; height:100%; color:var(--text-secondary); font-size:0.9rem;">
                <i data-lucide="coffee" style="margin-bottom:8px; width:32px; height:32px; opacity:0.5;"></i> Aún no hay ingresos hoy
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('modals')
<div class="modal-overlay" id="modal-local">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i data-lucide="plus-circle"></i> Registrar Venta Local</div>
            <button class="modal-close" onclick="cerrarModalLocal()"><i data-lucide="x"></i></button>
        </div>
        <form id="form-local" onsubmit="guardarServicioLocal(event)">
            <div class="form-group">
                <label class="form-label">Teléfono (Opcional)</label>
                <input type="text" id="local_telefono" class="form-control" placeholder="Ej: 5212223008628 (Para sumar puntos)">
            </div>
            <div class="form-group">
                <label class="form-label">Nombre del Cliente (Opcional)</label>
                <input type="text" id="local_nombre" class="form-control" placeholder="Ej: Juan Pérez">
            </div>
            <div class="form-group">
                <label class="form-label">Servicios Realizados</label>
                <div class="checkbox-group">
                    @foreach($servicios ?? [] as $srv)
                    <label class="checkbox-label">
                        <input type="checkbox" name="servicios[]" value="{{ $srv->id }}" data-precio="{{ $srv->precio }}" onchange="calcularPrecioLocal()">
                        {{ $srv->nombre }} (${{ number_format($srv->precio, 2) }})
                    </label>
                    @endforeach
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Especialista / Barbero</label>
                <select id="local_barbero_id" class="form-control" required>
                    <option value="">Selecciona un especialista...</option>
                    @foreach($barberos ?? [] as $barb)
                    <option value="{{ $barb->id }}">{{ $barb->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Precio Cobrado ($)</label>
                <input type="number" step="0.01" id="local_precio" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;"><i data-lucide="save"></i> Guardar Venta</button>
        </form>
    </div>
</div>
@endpush

@push('scripts-bottom')
<script>
    let periodoActual = '1m';
    const charts = {};

    function getGridColor() {
        return document.documentElement.getAttribute('data-theme') === 'dark'
            ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
    }
    function C() {
        return document.documentElement.getAttribute('data-theme') === 'dark'
            ? {gold:'#d4a853',green:'#34d399',red:'#f87171',blue:'#60a5fa',purple:'#a78bfa',orange:'#fb923c',cyan:'#22d3ee',pink:'#f472b6'}
            : {gold:'#2563eb',green:'#059669',red:'#dc2626',blue:'#3b82f6',purple:'#7c3aed',orange:'#ea580c',cyan:'#0891b2',pink:'#db2777'};
    }
    function getPalette() {
        const c = C();
        return [c.gold, c.blue, c.green, c.purple, c.orange, c.cyan, c.pink, c.red];
    }

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

    window.addEventListener('themeChanged', () => {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        if (typeof Chart !== 'undefined') {
            Chart.defaults.color = isDark ? '#94a3b8' : '#64748b';
            Chart.defaults.borderColor = getGridColor();
        }
        cargarDatos(periodoActual);
    });

    document.addEventListener('DOMContentLoaded', () => {
        initFiltros();
        cargarCitasPendientes();
        cargarDatos(periodoActual);
        setInterval(cargarCitasPendientes, 60000);
    });

    function escHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    async function cargarCitasPendientes() {
        try {
            const res = await fetch('/admin/citas-pendientes');
            const citas = await res.json();
            document.getElementById('citas-count').textContent = citas.length;
            const tbody = document.getElementById('citas-tbody');
            if (citas.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7"><div class="empty-state"><span class="empty-icon"><i data-lucide="party-popper" style="width:3rem;height:3rem;"></i></span><p>¡No hay citas pendientes por ahora!</p></div></td></tr>`;
                if (typeof lucide !== 'undefined') lucide.createIcons();
                return;
            }
            tbody.innerHTML = citas.map(c => `
                <tr>
                    <td class="cliente-cell"><strong>${escHtml(c.cliente)}</strong><span>${escHtml(c.telefono)}</span></td>
                    <td>${escHtml(c.servicios)}</td>
                    <td>${escHtml(c.fecha)}</td>
                    <td><strong>${escHtml(c.hora)}</strong></td>
                    <td>${escHtml(c.barbero)}</td>
                    <td><span class="badge badge-${c.estado}">${c.estado === 'pendiente'
                        ? '<i data-lucide="loader" style="width:12px;height:12px;vertical-align:middle;margin-right:2px;"></i> Pendiente'
                        : '<i data-lucide="check-circle" style="width:12px;height:12px;vertical-align:middle;margin-right:2px;"></i> Confirmada'
                    }</span></td>
                    <td><button class="btn btn-primary" style="padding:6px 12px;font-size:0.8rem;" onclick="completarCita(${c.id})"><i data-lucide="check"></i> Cobrar</button></td>
                </tr>
            `).join('');
            if (typeof lucide !== 'undefined') lucide.createIcons();
        } catch (e) {
            console.error('Error cargando citas pendientes', e);
        }
    }

    async function completarCita(id) {
        if (!confirm('¿Marcar cita como completada?')) return;
        try {
            const res = await fetch(`/admin/citas/${id}/completar`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });
            if (res.ok) { cargarCitasPendientes(); cargarDatos(periodoActual); }
        } catch (e) { alert('Error al completar cita'); }
    }

    async function cargarDatos(periodo) {
        try {
            const res = await fetch(`/admin/datos?periodo=${periodo}`);
            const json = await res.json();

            actualizarKpis(json.kpis);

            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            const darkBorder = isDark ? '#1e293b' : '#ffffff';

            renderChart('chart-ingresos', 'line', json.ingresos_por_dia.labels, [{
                label: 'Ingresos',
                data: json.ingresos_por_dia.data,
                borderColor: C().blue,
                backgroundColor: isDark ? 'rgba(96,165,250,0.1)' : 'rgba(37,99,235,0.1)',
                borderWidth: 3, fill: true, tension: 0.4, pointRadius: 4, pointBackgroundColor: C().blue
            }]);

            renderChart('chart-servicios', 'doughnut', json.servicios_populares.labels, [{
                data: json.servicios_populares.data,
                backgroundColor: getPalette(),
                borderWidth: 2, borderColor: darkBorder
            }], { cutout: '65%' });

            if (json.ingresos_hoy && json.ingresos_hoy.labels && json.ingresos_hoy.labels.length > 0) {
                document.getElementById('chart-ingresos-hoy').style.display = 'block';
                document.getElementById('empty-ingresos-hoy').style.display = 'none';

                renderChart('chart-ingresos-hoy', 'pie', json.ingresos_hoy.labels, [{
                    data: json.ingresos_hoy.data,
                    backgroundColor: [C().gold, C().blue, C().green, C().purple, C().orange, C().cyan],
                    borderWidth: 2, borderColor: darkBorder
                }], {
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return '  $' + Number(context.raw).toLocaleString('es-MX');
                                }
                            }
                        },
                        title: {
                            display: true,
                            text: `Total hoy: $${Number(json.ingresos_hoy.total).toLocaleString('es-MX')}`,
                            color: isDark ? '#94a3b8' : '#64748b'
                        }
                    }
                });
            } else {
                document.getElementById('chart-ingresos-hoy').style.display = 'none';
                document.getElementById('empty-ingresos-hoy').style.display = 'flex';
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }

            if (json.servicios_hoy && json.servicios_hoy.labels && json.servicios_hoy.labels.length > 0) {
                document.getElementById('chart-servicios-hoy').style.display = 'block';
                document.getElementById('empty-servicios-hoy').style.display = 'none';
                
                renderChart('chart-servicios-hoy', 'doughnut', json.servicios_hoy.labels, [{
                    data: json.servicios_hoy.data,
                    backgroundColor: [C().green, C().blue, C().cyan, C().purple, C().gold, C().orange, C().pink, C().red],
                    borderWidth: 2, borderColor: darkBorder
                }], {
                    cutout: '65%',
                    plugins: {
                        title: {
                            display: true,
                            text: `Total hoy: ${json.servicios_hoy.total} servicios`,
                            color: isDark ? '#94a3b8' : '#64748b'
                        }
                    }
                });
            } else {
                document.getElementById('chart-servicios-hoy').style.display = 'none';
                document.getElementById('empty-servicios-hoy').style.display = 'flex';
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }

            document.getElementById('periodo-label').textContent = `Mostrando datos de: ${json.fecha_inicio} a ${json.fecha_fin}`;
        } catch (e) {
            console.error('Error al cargar datos', e);
        }
    }

    function renderChart(id, type, labels, datasets, extraOptions = {}) {
        const canvas = document.getElementById(id);
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        if (charts[id]) charts[id].destroy();

        const baseOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: type === 'line' ? 'top' : 'right',
                    labels: { font: { family: 'Inter' } }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const raw = context.raw ?? 0;
                            const label = context.dataset.label || context.label || '';
                            if (id === 'chart-ingresos') {
                                return '  $' + Number(raw).toLocaleString('es-MX');
                            }
                            return '  ' + label + ': ' + raw;
                        }
                    }
                }
            }
        };

        if (type === 'line' || type === 'bar') {
            baseOptions.scales = {
                x: { grid: { color: getGridColor() } },
                y: {
                    grid: { color: getGridColor() },
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return id === 'chart-ingresos' ? '$' + value.toLocaleString('es-MX') : value;
                        }
                    }
                }
            };
        }

        const options = Object.assign({}, baseOptions, extraOptions);
        if (extraOptions.plugins) {
            options.plugins = { ...baseOptions.plugins, ...extraOptions.plugins };
        }

        charts[id] = new Chart(ctx, { type, data: { labels, datasets }, options });
    }

    function actualizarKpis(kpis) {
        animarNumero('kpi-ingresos', kpis.ingresos, '$', '');
        animarNumero('kpi-citas', kpis.total_citas);
        animarNumero('kpi-completadas', kpis.completadas);
        animarNumero('kpi-clientes', kpis.clientes_nuevos);
        animarNumero('kpi-cancelacion', kpis.tasa_cancelacion, '', '%');
    }

    function animarNumero(id, target, prefix = '', suffix = '') {
        const el = document.getElementById(id);
        if (!el) return;
        const duration = 800;
        const start = performance.now();
        function update(now) {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            const cur = Math.round(target * eased);
            el.textContent = prefix === '$'
                ? `$${cur.toLocaleString('es-MX')}`
                : `${prefix}${cur.toLocaleString('es-MX')}${suffix}`;
            if (progress < 1) requestAnimationFrame(update);
        }
        requestAnimationFrame(update);
    }

    function descargarPdf(e) {
        e.preventDefault();
        window.location.href = `/admin/reporte-pdf?periodo=${periodoActual}`;
    }

    function abrirModalLocal() {
        document.getElementById('form-local').reset();
        document.getElementById('local_precio').value = '0.00';
        document.getElementById('modal-local').classList.add('active');
    }
    function cerrarModalLocal() {
        document.getElementById('modal-local').classList.remove('active');
    }
    function calcularPrecioLocal() {
        let t = 0;
        document.querySelectorAll('input[name="servicios[]"]:checked').forEach(cb => t += parseFloat(cb.dataset.precio));
        document.getElementById('local_precio').value = t.toFixed(2);
    }

    async function guardarServicioLocal(e) {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        const originalContent = btn.innerHTML;
        btn.innerHTML = '<i data-lucide="loader"></i> Guardando...';
        btn.disabled = true;
        if (typeof lucide !== 'undefined') lucide.createIcons();

        const servicios = Array.from(document.querySelectorAll('input[name="servicios[]"]:checked')).map(cb => cb.value);
        if (servicios.length === 0) {
            alert('Debes seleccionar al menos un servicio.');
            btn.innerHTML = originalContent;
            btn.disabled = false;
            if (typeof lucide !== 'undefined') lucide.createIcons();
            return;
        }

        const data = {
            telefono: document.getElementById('local_telefono').value.trim(),
            nombre: document.getElementById('local_nombre').value.trim(),
            servicios,
            barbero_id: document.getElementById('local_barbero_id').value,
            precio_cobrado: document.getElementById('local_precio').value,
            _token: '{{ csrf_token() }}'
        };

        try {
            const res = await fetch('{{ route("admin.citas.local") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(data)
            });
            if (res.ok) {
                cerrarModalLocal();
                cargarCitasPendientes();
                cargarDatos(periodoActual);
            } else {
                const err = await res.json();
                alert(err.message || 'Error al guardar el servicio');
            }
        } catch (error) {
            alert('Error de conexión');
        } finally {
            btn.innerHTML = originalContent;
            btn.disabled = false;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    }
</script>
@endpush