@extends('layouts.admin')

@section('title', 'Empleados y Especialistas')

@push('styles')
    <style>
        .page-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; animation: fadeUp 0.3s ease forwards; opacity: 0; }
        .page-title h1 { font-size: 1.8rem; font-weight: 700; color: var(--text-primary); }
        .page-title p { color: var(--text-secondary); margin-top: 4px; font-size: 0.95rem; }
        
        .empleados-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px; animation: fadeUp 0.5s ease forwards; opacity: 0; }
        .empleado-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 24px; box-shadow: var(--shadow-sm); display: flex; flex-direction: column; gap: 20px; transition: transform 0.2s, box-shadow 0.2s; }
        .empleado-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); }
        .empleado-card.inactivo { opacity: 0.6; filter: grayscale(1); }
        
        .emp-header { display: flex; align-items: center; gap: 16px; }
        .emp-avatar { width: 56px; height: 56px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 700; flex-shrink: 0; }
        .emp-info { flex: 1; min-width: 0; }
        .emp-info h3 { font-size: 1.2rem; font-weight: 700; color: var(--text-primary); margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .emp-info p { font-size: 0.85rem; color: var(--text-secondary); display: flex; align-items: center; gap: 4px; }
        
        .emp-stats { display: flex; gap: 16px; border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); padding: 16px 0; }
        .stat-item { flex: 1; text-align: center; }
        .stat-val { font-size: 1.25rem; font-weight: 800; color: var(--text-primary); }
        .stat-lbl { font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 600; margin-top: 4px; }
        
        .emp-actions { display: flex; gap: 8px; margin-top: auto; }
        .btn-sm { padding: 8px 12px; font-size: 0.8rem; border-radius: 8px; flex: 1; justify-content: center; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary); cursor: pointer; transition: all 0.2s; }
        .btn-sm:hover { background: var(--border-color); }
        .btn-danger-sm { background: #fee2e2; color: #ef4444; border: none; padding: 8px; border-radius: 8px; cursor: pointer; transition: all 0.2s; }
        .btn-danger-sm:hover { background: #fecaca; }

        @keyframes fadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
        
        .color-picker { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px; }
        .color-option { width: 32px; height: 32px; border-radius: 50%; cursor: pointer; border: 2px solid transparent; transition: transform 0.2s; }
        .color-option:hover { transform: scale(1.1); }
        .color-option.selected { border-color: var(--text-primary); box-shadow: 0 0 0 2px var(--bg-card); }
    </style>
@endpush

@section('content')
<div class="page-header">
    <div class="page-title">
        <h1>Especialistas</h1>
        <p>Administra los barberos y su rendimiento</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="abrirModal(null)"><i data-lucide="plus"></i> Nuevo Especialista</button>
    </div>
</div>

@if(session('success'))
    <div style="background: rgba(16, 185, 129, 0.1); color: #059669; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 500;">
        {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 500;">
        <ul style="margin: 0; padding-left: 20px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="empleados-grid">
    @foreach($empleados as $emp)
    <div class="empleado-card {{ !$emp->activo ? 'inactivo' : '' }}">
        <div class="emp-header">
            <div class="emp-avatar" style="background: {{ $emp->color_calendario }}20; color: {{ $emp->color_calendario }}; border: 2px solid {{ $emp->color_calendario }};">
                {{ substr($emp->nombre, 0, 1) }}
            </div>
            <div class="emp-info">
                <h3>{{ $emp->nombre }}</h3>
                <p><i data-lucide="phone" style="width: 14px; height: 14px;"></i> {{ $emp->telefono ?: 'Sin teléfono' }}</p>
                @if($emp->user)
                <p style="margin-top: 4px; font-size: 0.8rem; background: var(--bg-primary); padding: 4px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; border: 1px solid var(--border-color); color: var(--text-secondary); max-width: 100%; box-sizing: border-box;">
                    <i data-lucide="mail" style="width: 12px; height: 12px; flex-shrink: 0;"></i> 
                    <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $emp->user->email }}</span>
                </p>
                @endif
                @if(!$emp->activo) <span style="font-size: 0.7rem; background: var(--border-color); padding: 2px 6px; border-radius: 4px; margin-top: 4px; display: inline-block;">INACTIVO</span> @endif
            </div>
        </div>
        
        <div class="emp-stats">
            <div class="stat-item">
                <div class="stat-val">{{ $emp->citas_completadas }}</div>
                <div class="stat-lbl">Citas Completadas</div>
            </div>
            <div class="stat-item">
                <div class="stat-val" style="color: var(--green);">${{ number_format($emp->ingresos_generados, 2) }}</div>
                <div class="stat-lbl">Ingresos</div>
            </div>
        </div>
        
        <div class="emp-actions">
            <button class="btn-sm" onclick="abrirModal({{ $emp }})">
                <i data-lucide="edit-2" style="width: 14px;"></i> Editar
            </button>
            @if($emp->activo)
            <form action="{{ route('admin.empleados.destroy', $emp->id) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas desactivar a este especialista? Ya no aparecerá en la agenda.');" style="margin:0;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-danger-sm" title="Desactivar">
                    <i data-lucide="trash-2" style="width: 16px;"></i>
                </button>
            </form>
            @endif
        </div>
    </div>
    @endforeach
</div>
@endsection

@push('modals')
<!-- Modal Empleado -->
<div class="modal-overlay" id="modal-empleado">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title" id="modal-title">Nuevo Especialista</div>
            <button class="modal-close" onclick="cerrarModal()"><i data-lucide="x"></i></button>
        </div>
        <form id="form-empleado" method="POST" action="{{ route('admin.empleados.store') }}">
            @csrf
            <input type="hidden" name="_method" id="form-method" value="POST">
            
            <div class="form-group">
                <label class="form-label">Nombre del Especialista *</label>
                <input type="text" name="nombre" id="emp_nombre" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Teléfono</label>
                <input type="text" name="telefono" id="emp_telefono" class="form-control">
            </div>
            
            <div class="form-group">
                <label class="form-label">Correo de Acceso *</label>
                <input type="email" name="email" id="emp_email" class="form-control" required placeholder="correo@ejemplo.com">
                <small style="color: var(--text-secondary); display: block; margin-top: 4px;">Este será el correo que usará para iniciar sesión.</small>
            </div>
            
            <div class="form-group">
                <label class="form-label">Contraseña Inicial *</label>
                <input type="text" name="password" id="emp_password" class="form-control">
                <small style="color: var(--text-secondary); display: block; margin-top: 4px;">El administrador define la primera contraseña y se la pasa al especialista.</small>
            </div>
            
            <div class="form-group">
                <label class="form-label">Color para la Agenda *</label>
                <input type="hidden" name="color_calendario" id="emp_color" value="#3b82f6" required>
                <div class="color-picker" id="color-picker">
                    <!-- Colores -->
                    @php
                        $colores = ['#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4', '#64748b'];
                    @endphp
                    @foreach($colores as $c)
                        <div class="color-option" style="background: {{ $c }};" data-color="{{ $c }}" onclick="seleccionarColor('{{ $c }}')"></div>
                    @endforeach
                </div>
            </div>
            
            <div class="form-group" id="group-activo" style="display: none; margin-top: 24px;">
                <label class="checkbox-label" style="font-weight: 600;">
                    <input type="checkbox" name="activo" id="emp_activo" value="1">
                    Especialista Activo (Visible en agenda)
                </label>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 24px;">
                Guardar Especialista
            </button>
        </form>
    </div>
</div>
@endpush

@push('scripts-bottom')
<script>
    function seleccionarColor(hex) {
        document.getElementById('emp_color').value = hex;
        document.querySelectorAll('.color-option').forEach(el => {
            el.classList.remove('selected');
            if (el.dataset.color === hex) el.classList.add('selected');
        });
    }

    function abrirModal(empleado = null) {
        const form = document.getElementById('form-empleado');
        const modalTitle = document.getElementById('modal-title');
        const methodInput = document.getElementById('form-method');
        const groupActivo = document.getElementById('group-activo');
        
        if (empleado) {
            modalTitle.textContent = 'Editar Especialista';
            form.action = `/admin/empleados/${empleado.id}`;
            methodInput.value = 'PUT';
            
            document.getElementById('emp_nombre').value = empleado.nombre;
            document.getElementById('emp_telefono').value = empleado.telefono || '';
            document.getElementById('emp_email').value = (empleado.user && empleado.user.email) ? empleado.user.email : '';
            document.getElementById('emp_password').value = '';
            document.getElementById('emp_password').required = false;
            document.getElementById('emp_password').placeholder = 'Déjalo en blanco si no quieres cambiarla';
            seleccionarColor(empleado.color_calendario);
            
            groupActivo.style.display = 'block';
            document.getElementById('emp_activo').checked = empleado.activo;
        } else {
            modalTitle.textContent = 'Nuevo Especialista';
            form.action = `{{ route('admin.empleados.store') }}`;
            methodInput.value = 'POST';
            
            document.getElementById('emp_nombre').value = '';
            document.getElementById('emp_telefono').value = '';
            document.getElementById('emp_email').value = '';
            document.getElementById('emp_password').value = '';
            document.getElementById('emp_password').required = true;
            document.getElementById('emp_password').placeholder = 'Mínimo 8 caracteres';
            seleccionarColor('#3b82f6');
            
            groupActivo.style.display = 'none';
        }
        
        document.getElementById('modal-empleado').classList.add('active');
    }
    
    function cerrarModal() {
        document.getElementById('modal-empleado').classList.remove('active');
    }
    
    // Iniciar con color azul seleccionado por defecto
    seleccionarColor('#3b82f6');
</script>
@endpush
