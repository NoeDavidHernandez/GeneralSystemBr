@extends('layouts.admin')

@section('title', 'Agenda')

@push('scripts')
    <!-- FullCalendar Scheduler (incluye vistas por recurso/columna) -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar-scheduler@6.1.10/index.global.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.10/locales/es.global.min.js'></script>
@endpush

@push('styles')
    <style>
        .fc-theme-standard td, .fc-theme-standard th { border-color: var(--border-color); }
        .fc-theme-standard .fc-scrollgrid { border: none; }
        .fc-scrollgrid-sync-inner { border: none; }
        
        /* Eliminar el fondo amarillo feo del día actual */
        .fc .fc-timegrid-col.fc-day-today { background-color: transparent !important; }
        
        /* Líneas de cuadrícula sutiles */
        .fc-timegrid-slot-lane { border-bottom: 1px solid var(--bg-card-hover) !important; }
        .fc-timegrid-slot-minor .fc-timegrid-slot-lane { border-bottom: 1px dashed var(--border-color) !important; }
        
        /* Etiquetas de tiempo */
        .fc-timegrid-slot-label { border: none !important; }
        .fc-timegrid-slot-label-cushion { color: var(--text-secondary); font-weight: 500; font-size: 0.8rem; text-transform: lowercase; }
        
        .fc .fc-toolbar-title { font-size: 1.5rem; font-family: 'Inter', sans-serif; font-weight: 700; color: var(--text-primary); text-transform: capitalize; }
        .fc .fc-button-primary { background-color: var(--bg-card); color: var(--text-primary); border-color: var(--border-color); border-radius: 8px; font-weight: 600; font-family: 'Inter', sans-serif; text-transform: capitalize; transition: all 0.2s; box-shadow: var(--shadow-sm); padding: 8px 16px; }
        .fc .fc-button-primary:hover { background-color: var(--bg-card-hover); color: var(--text-primary); border-color: var(--border-color); }
        .fc .fc-button-primary:not(:disabled).fc-button-active, .fc .fc-button-primary:not(:disabled):active { background-color: var(--accent); color: var(--accent-btn-text); border-color: var(--accent); box-shadow: inset 0 2px 4px rgba(0,0,0,0.1); }
        
        .fc-col-header-cell-cushion { padding: 0 !important; width: 100%; text-decoration: none; }
        
        /* Eventos (Tarjetas de Citas) */
        .fc-v-event { background: transparent !important; border: none !important; box-shadow: none !important; margin: 0 6px; }
        .fc-event-main { padding: 0 !important; height: 100%; }
        
        /* Indicador de hora actual más sutil */
        .fc .fc-timegrid-now-indicator-line { border-color: var(--red); border-width: 1px; }
        .fc .fc-timegrid-now-indicator-arrow { border-color: var(--red); border-width: 4px; }

        .agenda-container { background: var(--bg-card); padding: 24px; border-radius: 16px; box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); min-height: 700px; animation: fadeUp 0.4s ease forwards; opacity: 0; }
        
        @keyframes fadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }

        /* Modal Info Cita */
        .info-header { padding: 24px; border-bottom: 1px solid var(--border-color); color: white; border-radius: 16px 16px 0 0; }
        .info-header h3 { font-size: 1.4rem; font-weight: 700; margin-bottom: 6px; display: flex; align-items: center; gap: 8px; }
        .info-header p { font-size: 0.95rem; opacity: 0.9; font-weight: 500; }
        .info-content { padding: 24px; display: flex; flex-direction: column; gap: 16px; }
        .info-row { display: flex; align-items: flex-start; gap: 12px; color: var(--text-primary); font-size: 0.95rem; }
        .info-row i { color: var(--text-secondary); margin-top: 2px; }
        .info-badge { display: inline-block; padding: 6px 12px; border-radius: 100px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; background: var(--bg-primary); color: var(--text-secondary); border: 1px solid var(--border-color); }

        .fc .fc-timegrid-now-indicator-line { border-color: var(--red); border-width: 2px; }
        .fc .fc-timegrid-now-indicator-arrow { border-color: var(--red); border-width: 6px; }
    </style>
@endpush

@section('content')
<div class="page-header">
    <div class="page-title">
        <h1>Agenda por Especialista</h1>
        <p>Arrastra las citas para reagendarlas o da clic para ver detalles.</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="abrirModalNuevaCita()"><i data-lucide="plus"></i> Nueva Cita</button>
    </div>
</div>

<div class="agenda-container">
    <div id="calendar"></div>
</div>
@endsection

@push('modals')
<!-- Modal Detalles Cita -->
<div class="modal-overlay" id="modal-detalle">
    <div class="modal" style="padding: 0; overflow: hidden;">
        <div class="info-header" id="detalle-header" style="background: var(--accent);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <h3 id="detalle-cliente"><i data-lucide="user"></i> Cliente</h3>
                    <p id="detalle-fecha">Fecha y Hora</p>
                </div>
                <button class="modal-close" onclick="cerrarModalDetalle()" style="color: white; opacity: 0.8;"><i data-lucide="x"></i></button>
            </div>
        </div>
        <div class="info-content">
            <div class="info-row">
                <i data-lucide="scissors"></i>
                <div>
                    <strong style="display: block; margin-bottom: 4px;">Servicios:</strong>
                    <span id="detalle-servicios">...</span>
                </div>
            </div>
            <div class="info-row">
                <i data-lucide="dollar-sign"></i>
                <div>
                    <strong style="display: block; margin-bottom: 4px;">Total a cobrar:</strong>
                    <span id="detalle-precio" style="font-weight: 700; color: var(--accent);">...</span>
                </div>
            </div>
            <div class="info-row">
                <i data-lucide="phone"></i>
                <div>
                    <strong style="display: block; margin-bottom: 4px;">Teléfono:</strong>
                    <span id="detalle-telefono">...</span>
                </div>
            </div>
            <div class="info-row">
                <i data-lucide="info"></i>
                <div>
                    <strong style="display: block; margin-bottom: 4px;">Estado:</strong>
                    <span id="detalle-estado" class="info-badge">...</span>
                </div>
            </div>
            
            <div style="display: flex; gap: 12px; margin-top: 12px; border-top: 1px solid var(--border-color); padding-top: 20px;">
                <button class="btn" style="flex: 1; justify-content: center; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary);" onclick="cerrarModalDetalle()">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nueva Cita -->
<div class="modal-overlay" id="modal-nueva">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i data-lucide="calendar-plus"></i> Agendar Cita</div>
            <button class="modal-close" onclick="cerrarModalNuevaCita()"><i data-lucide="x"></i></button>
        </div>
        <form id="form-nueva-cita" onsubmit="guardarNuevaCita(event)">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label">Nombre del Cliente *</label>
                    <input type="text" id="nc_nombre" class="form-control" required placeholder="Ej: Carlos Gómez">
                </div>
                <div class="form-group">
                    <label class="form-label">Teléfono</label>
                    <input type="text" id="nc_telefono" class="form-control" placeholder="Opcional">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Especialista *</label>
                <select id="nc_barbero" class="form-control" required>
                    <option value="">Selecciona un especialista...</option>
                    @foreach($barberos as $barbero)
                        <option value="{{ $barbero->id }}">{{ $barbero->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Servicios *</label>
                <div class="checkbox-group" style="max-height: 140px;">
                    @foreach($servicios as $srv)
                    <label class="checkbox-label">
                        <input type="checkbox" name="nc_servicios[]" value="{{ $srv->id }}">
                        {{ $srv->nombre }} (${{ number_format($srv->precio, 2) }})
                    </label>
                    @endforeach
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label">Fecha *</label>
                    <input type="date" id="nc_fecha" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Hora de Inicio *</label>
                    <input type="time" id="nc_hora" class="form-control" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 8px;">
                <i data-lucide="save"></i> Guardar Cita
            </button>
        </form>
    </div>
</div>
@endpush

@push('scripts-bottom')
<script>
    let calendar;

    document.addEventListener('DOMContentLoaded', function() {
        const calendarEl = document.getElementById('calendar');
        const barberos = @json($barberos);
        
        // Preparar recursos para FullCalendar
        const resources = barberos.map(b => ({
            id: b.id.toString(),
            title: b.nombre,
            eventColor: b.color_hex || '#3b82f6'
        }));

        calendar = new FullCalendar.Calendar(calendarEl, {
            schedulerLicenseKey: 'CC-Any', // Desactiva el warning para uso local/desarrollo
            locale: 'es',
            timeZone: 'local',
            initialView: 'resourceTimeGridDay', // FORZAMOS VISTA DE COLUMNAS SIEMPRE
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'resourceTimeGridDay,timeGridWeek,dayGridMonth'
            },
            views: {
                resourceTimeGridDay: { buttonText: 'Día' },
                timeGridWeek: { buttonText: 'Semana' },
                dayGridMonth: { buttonText: 'Mes' }
            },
            resources: resources,
            events: '{{ route("admin.agenda.eventos") }}',
            editable: true,
            selectable: true,
            selectMirror: true,
            dayMaxEvents: true,
            expandRows: true, // Expande las filas para ocupar todo el alto
            slotMinTime: '08:00:00',
            slotMaxTime: '22:00:00',
            slotDuration: '00:15:00', // Filas de 15 minutos para dar más espacio vertical
            slotLabelInterval: '01:00', // Solo muestra la etiqueta cada hora
            slotLabelFormat: { hour: 'numeric', omitZeroMinute: true, meridiem: 'short' },
            allDaySlot: false,
            nowIndicator: true,
            
            // ─── CUSTOM RENDERING PARA RECURSOS (BARBEROS) ───
            resourceLabelContent: function(arg) {
                const color = arg.resource.extendedProps.eventColor || 'var(--accent)';
                const initials = arg.resource.title.substring(0, 2).toUpperCase();
                return {
                    html: `
                    <div style="display:flex; flex-direction:column; align-items:center; padding:16px 0 8px 0; background:var(--bg-card); cursor:default; width:100%;">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <div style="width:36px; height:36px; border-radius:50%; background:${color}20; color:${color}; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.9rem; flex-shrink:0;">
                                ${initials}
                            </div>
                            <div style="text-align:left;">
                                <div style="font-weight:700; color:var(--text-primary); font-size:0.95rem; line-height:1.2;">${arg.resource.title}</div>
                                <div style="font-size:0.75rem; color:var(--text-secondary); font-weight:500;">Especialista</div>
                            </div>
                        </div>
                        <div style="height:4px; width:40px; background:${color}; border-radius:4px; margin-top:12px;"></div>
                    </div>`
                };
            },
            
            // ─── CUSTOM RENDERING PARA EVENTOS (CITAS) ───
            eventContent: function(arg) {
                const props = arg.event.extendedProps;
                const color = arg.event.backgroundColor || 'var(--accent)';
                
                return {
                    html: `
                    <div style="background:${color}15; border-left:4px solid ${color}; border-radius:8px; padding:6px 10px; height:100%; display:flex; flex-direction:column; gap:4px; transition:transform 0.2s, box-shadow 0.2s; cursor:pointer;"
                         onmouseover="this.style.transform='scale(1.02)'; this.style.boxShadow='var(--shadow-md)'"
                         onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='none'">
                        <div style="font-weight:700; color:var(--text-primary); font-size:0.85rem; line-height:1.2;">${props.cliente}</div>
                        <div style="color:var(--text-secondary); font-size:0.75rem; line-height:1.2; font-weight:500;">${props.servicios}</div>
                        ${props.estado ? `<div style="margin-top:auto;"><span style="background:${color}; color:#fff; padding:2px 8px; border-radius:100px; font-size:0.65rem; font-weight:700; text-transform:uppercase; letter-spacing:0.02em;">${props.estado}</span></div>` : ''}
                    </div>`
                };
            },
            
            // Al hacer clic en un horario vacío
            select: function(info) {
                abrirModalNuevaCita(info.start, info.resource ? info.resource.id : null);
            },
            
            // Al hacer clic en una cita existente
            eventClick: function(info) {
                mostrarDetallesCita(info.event);
            },
            
            // Al arrastrar y soltar (reagendar)
            eventDrop: function(info) {
                actualizarCita(info.event, info.revert);
            },
            
            // Al cambiar duración (hacer más larga o corta)
            eventResize: function(info) {
                actualizarCita(info.event, info.revert);
            }
        });

        calendar.render();
        
        // Actualizar colores si cambia el tema
        window.addEventListener('themeChanged', () => {
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            document.documentElement.style.setProperty('--fc-border-color', isDark ? '#334155' : '#e2e8f0');
            document.documentElement.style.setProperty('--fc-page-bg-color', isDark ? '#1e293b' : '#ffffff');
            document.documentElement.style.setProperty('--fc-neutral-bg-color', isDark ? '#0f172a' : '#f8fafc');
        });
        window.dispatchEvent(new Event('themeChanged')); // Forzar color inicial
    });

    // ─── LÓGICA DE DETALLES ──────────────────────────────────────
    function mostrarDetallesCita(event) {
        const props = event.extendedProps;
        
        document.getElementById('detalle-cliente').innerHTML = `<i data-lucide="user"></i> ${props.cliente}`;
        
        const formatOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
        document.getElementById('detalle-fecha').textContent = event.start.toLocaleDateString('es-ES', formatOptions);
        
        document.getElementById('detalle-servicios').textContent = props.servicios || 'Ninguno';
        document.getElementById('detalle-precio').textContent = props.precio;
        document.getElementById('detalle-telefono').textContent = props.telefono || 'No registrado';
        document.getElementById('detalle-estado').textContent = props.estado;
        
        document.getElementById('detalle-header').style.background = event.backgroundColor;
        
        document.getElementById('modal-detalle').classList.add('active');
        if(typeof lucide !== 'undefined') lucide.createIcons();
    }
    
    function cerrarModalDetalle() {
        document.getElementById('modal-detalle').classList.remove('active');
    }

    // ─── LÓGICA DE CREAR CITA ────────────────────────────────────
    function abrirModalNuevaCita(fechaInicio = null, barberoId = null) {
        document.getElementById('form-nueva-cita').reset();
        
        if (fechaInicio) {
            // Formatear a YYYY-MM-DD
            const yyyy = fechaInicio.getFullYear();
            const mm = String(fechaInicio.getMonth() + 1).padStart(2, '0');
            const dd = String(fechaInicio.getDate()).padStart(2, '0');
            document.getElementById('nc_fecha').value = `${yyyy}-${mm}-${dd}`;
            
            // Formatear a HH:mm
            const hh = String(fechaInicio.getHours()).padStart(2, '0');
            const mins = String(fechaInicio.getMinutes()).padStart(2, '0');
            document.getElementById('nc_hora').value = `${hh}:${mins}`;
        } else {
            const now = new Date();
            const yyyy = now.getFullYear();
            const mm = String(now.getMonth() + 1).padStart(2, '0');
            const dd = String(now.getDate()).padStart(2, '0');
            document.getElementById('nc_fecha').value = `${yyyy}-${mm}-${dd}`;
            document.getElementById('nc_hora').value = '10:00';
        }
        
        if (barberoId) {
            document.getElementById('nc_barbero').value = barberoId;
        }
        
        document.getElementById('modal-nueva').classList.add('active');
    }
    
    function cerrarModalNuevaCita() {
        document.getElementById('modal-nueva').classList.remove('active');
    }
    
    async function guardarNuevaCita(e) {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i data-lucide="loader"></i> Guardando...';
        btn.disabled = true;
        if(typeof lucide !== 'undefined') lucide.createIcons();

        const servicios = Array.from(document.querySelectorAll('input[name="nc_servicios[]"]:checked')).map(cb => cb.value);
        if (servicios.length === 0) {
            alert('Debes seleccionar al menos un servicio.');
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            return;
        }

        const data = {
            cliente_nombre: document.getElementById('nc_nombre').value,
            cliente_telefono: document.getElementById('nc_telefono').value,
            barbero_id: document.getElementById('nc_barbero').value,
            servicios: servicios,
            fecha: document.getElementById('nc_fecha').value,
            hora_inicio: document.getElementById('nc_hora').value,
            _token: '{{ csrf_token() }}'
        };

        try {
            const res = await fetch('{{ route("admin.agenda.guardar") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(data)
            });
            
            if (res.ok) {
                cerrarModalNuevaCita();
                calendar.refetchEvents();
            } else {
                const err = await res.json();
                alert(err.message || 'Error al guardar la cita');
            }
        } catch (error) {
            alert('Error de conexión');
        } finally {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            if(typeof lucide !== 'undefined') lucide.createIcons();
        }
    }

    // ─── LÓGICA DE REAGENDAR (Arrastrar y Soltar) ────────────────
    async function actualizarCita(event, revertFunc) {
        if (!confirm(`¿Confirmas el cambio de horario para ${event.title}?`)) {
            revertFunc();
            return;
        }

        const pad = (n) => String(n).padStart(2, '0');
        const formatTime = (d) => `${pad(d.getHours())}:${pad(d.getMinutes())}:00`;
        const formatDate = (d) => `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;

        const data = {
            _method: 'PUT',
            _token: '{{ csrf_token() }}',
            fecha: formatDate(event.start),
            hora_inicio: formatTime(event.start),
            hora_fin: event.end ? formatTime(event.end) : null,
            barbero_id: event.getResources().length > 0 ? event.getResources()[0].id : event.extendedProps.resourceId || document.getElementById('nc_barbero').options[1].value // Fallback si no hay resourceId
        };
        
        // Si no se capturó la hora fin (evento arrastrado sin resize)
        if (!data.hora_fin) {
            // Simulamos sumarle 30 mins (o lo que dicten sus servicios, pero el server lo ajustará si es nulo)
            // En el controller update, si hora_fin no viene, lo ignoramos, pero aquí lo requerimos.
            const end = new Date(event.start.getTime() + 30*60000);
            data.hora_fin = formatTime(end);
        }

        try {
            const res = await fetch(`/admin/agenda/actualizar/${event.id}`, {
                method: 'POST', // porque usamos _method: PUT
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(data)
            });
            
            if (!res.ok) {
                throw new Error('Error al actualizar');
            }
        } catch (error) {
            alert('Error al guardar el cambio. Se revertirá la acción.');
            revertFunc();
        }
    }
</script>
@endpush
