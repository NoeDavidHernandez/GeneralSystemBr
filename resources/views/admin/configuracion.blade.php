@extends('layouts.admin')

@section('title', 'Configuración del Local')

@push('styles')
    <style>
        .page-header { margin-bottom: 24px; animation: fadeUp 0.3s ease forwards; opacity: 0; }
        .page-title h1 { font-size: 1.8rem; font-weight: 700; color: var(--text-primary); }
        .page-title p { color: var(--text-secondary); margin-top: 4px; font-size: 0.95rem; }
        
        .config-container { display: flex; gap: 24px; animation: fadeUp 0.4s ease forwards; opacity: 0; align-items: flex-start; flex-wrap: wrap; }
        
        .config-sidebar { width: 250px; background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border-color); padding: 12px; box-shadow: var(--shadow-sm); flex-shrink: 0; }
        .config-menu-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 8px; color: var(--text-secondary); font-weight: 600; cursor: pointer; transition: all 0.2s; font-size: 0.9rem; }
        .config-menu-item:hover { background: var(--bg-card-hover); color: var(--text-primary); }
        .config-menu-item.active { background: var(--sidebar-active-bg); color: var(--accent); }
        
        .config-content { flex: 1; min-width: 300px; background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); padding: 32px; }
        .config-section-title { font-size: 1.1rem; font-weight: 700; color: var(--text-primary); margin-bottom: 4px; }
        .config-section-desc { font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 24px; }
        
        .logo-upload-box { display: flex; align-items: center; gap: 24px; margin-bottom: 32px; padding-bottom: 24px; border-bottom: 1px solid var(--border-color); }
        .logo-preview { width: 80px; height: 80px; border-radius: 16px; background: var(--bg-card-hover); display: flex; align-items: center; justify-content: center; color: var(--accent); font-size: 2rem; border: 1px dashed var(--border-color); }
        .logo-actions h3 { font-size: 0.95rem; font-weight: 600; color: var(--text-primary); margin-bottom: 4px; }
        .logo-actions p { font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 12px; }
        .logo-btns { display: flex; gap: 8px; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group-full { grid-column: span 2; }
        
        .form-group label { display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px; }
        .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--border-color); border-radius: 10px; background: var(--bg-primary); color: var(--text-primary); font-family: inherit; font-size: 0.95rem; transition: border-color 0.3s ease; }
        .form-control:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        
        .form-actions { margin-top: 32px; display: flex; justify-content: flex-end; gap: 12px; padding-top: 24px; border-top: 1px solid var(--border-color); }
        .btn-outline { padding: 10px 20px; border: 1px solid var(--border-color); background: transparent; color: var(--text-primary); border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
        .btn-outline:hover { background: var(--bg-card-hover); }

        @keyframes fadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
        @media(max-width: 768px) { .form-grid { grid-template-columns: 1fr; } .form-group-full { grid-column: span 1; } }
    </style>
@endpush

@section('content')
<div class="page-header">
    <div class="page-title">
        <h1>Configuración</h1>
        <p>Personaliza la información pública de tu negocio</p>
    </div>
</div>

@if(session('success'))
    <div style="background: rgba(16, 185, 129, 0.1); color: #059669; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 500;">
        {{ session('success') }}
    </div>
@endif

<div class="config-container">
    <div class="config-sidebar">
        @if(Auth::user()->rol === 'admin')
        <div class="config-menu-item active" id="menu-perfil" onclick="switchTab('perfil')">
            <i data-lucide="building"></i> Perfil del local
        </div>
        @endif
        <div class="config-menu-item {{ Auth::user()->rol === 'empleado' ? 'active' : '' }}" id="menu-horarios" onclick="switchTab('horarios')">
            <i data-lucide="clock"></i> Horarios
        </div>
    </div>
    
    <div class="config-content">
        <form action="{{ route('admin.configuracion.update') }}" method="POST">
            @csrf
            @method('PUT')

            <!-- TAB: PERFIL -->
            @if(Auth::user()->rol === 'admin')
            <div id="tab-perfil">
                <h2 class="config-section-title">Información del local</h2>
                <p class="config-section-desc">Datos públicos que verán tus clientes en WhatsApp y la web</p>
            
            <div class="logo-upload-box">
                <div class="logo-preview">
                    <i data-lucide="scissors"></i>
                </div>
                <div class="logo-actions">
                    <h3>Logo del local</h3>
                    <p>PNG o JPG, mínimo 512x512px</p>
                    <div class="logo-btns">
                        <button type="button" class="btn-outline" style="padding: 6px 12px; font-size: 0.8rem;">Subir nuevo</button>
                        <button type="button" class="btn-outline" style="padding: 6px 12px; font-size: 0.8rem; border-color: transparent; color: var(--red);">Quitar</button>
                    </div>
                </div>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Nombre del Local</label>
                    <input type="text" name="nombre" class="form-control" value="{{ $barberia->nombre }}" required>
                </div>
                <div class="form-group">
                    <label>RIF / NIT (Opcional)</label>
                    <input type="text" name="rif" class="form-control" value="{{ $barberia->rif }}">
                </div>
                <div class="form-group">
                    <label>Teléfono de Contacto</label>
                    <input type="text" name="telefono" class="form-control" value="{{ $barberia->telefono }}">
                </div>
                <div class="form-group">
                    <label>Email de Contacto</label>
                    <input type="email" name="email" class="form-control" value="{{ $barberia->email }}">
                </div>
                <div class="form-group form-group-full">
                    <label>Dirección Física</label>
                    <input type="text" name="direccion" class="form-control" value="{{ $barberia->direccion }}">
                </div>
                <div class="form-group form-group-full">
                    <label>Descripción del Local</label>
                    <textarea name="descripcion" class="form-control" rows="3">{{ $barberia->descripcion }}</textarea>
                </div>
            </div>
            
            </div>
            @endif

            <!-- TAB: HORARIOS -->
            @php 
                $isAdmin = Auth::user()->rol === 'admin';
                $h = $isAdmin ? ($barberia->horario_json ?? []) : ($barbero->horario_propio_json ?? []); 
            @endphp
            <div id="tab-horarios" style="display: {{ $isAdmin ? 'none' : 'block' }};">
                <h2 class="config-section-title">{{ $isAdmin ? 'Horario de Operación' : 'Mis Horarios' }}</h2>
                <p class="config-section-desc">{{ $isAdmin ? 'Define cuándo está abierta la barbería y tu hora de comida' : 'Define tus horas de entrada, salida, comida y descanso' }}</p>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Hora de Apertura</label>
                        <input type="time" name="apertura" class="form-control" value="{{ $h['apertura'] ?? '09:00' }}">
                    </div>
                    <div class="form-group">
                        <label>Hora de Cierre</label>
                        <input type="time" name="cierre" class="form-control" value="{{ $h['cierre'] ?? '20:00' }}">
                    </div>
                    <div class="form-group">
                        <label>Inicio de Comida</label>
                        <input type="time" name="comida_inicio" class="form-control" value="{{ $h['comida_inicio'] ?? '14:00' }}">
                    </div>
                    <div class="form-group">
                        <label>Fin de Comida</label>
                        <input type="time" name="comida_fin" class="form-control" value="{{ $h['comida_fin'] ?? '15:00' }}">
                    </div>
                    
                    <div class="form-group form-group-full">
                        <label>Días Cerrados (Descanso)</label>
                        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                            @php $dias_cerrado = $h['dias_cerrado'] ?? []; @endphp
                            @foreach([1=>'Lunes', 2=>'Martes', 3=>'Miércoles', 4=>'Jueves', 5=>'Viernes', 6=>'Sábado', 0=>'Domingo'] as $num => $dia)
                                <label style="display:flex; align-items:center; gap:6px; cursor:pointer; font-weight:normal; text-transform:none; font-size:0.9rem;">
                                    <input type="checkbox" name="dias_cerrado[]" value="{{ $num }}" {{ in_array($num, $dias_cerrado) ? 'checked' : '' }}>
                                    {{ $dia }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn-outline" onclick="window.location.reload()">Cancelar</button>
                <button type="submit" class="btn btn-primary" style="background: var(--accent); color: var(--accent-btn-text);">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts-bottom')
<script>
function switchTab(tab) {
    // Esconder todo
    document.getElementById('tab-perfil').style.display = 'none';
    document.getElementById('tab-horarios').style.display = 'none';
    
    // Quitar active a menú
    document.getElementById('menu-perfil').classList.remove('active');
    document.getElementById('menu-horarios').classList.remove('active');
    
    // Mostrar lo seleccionado
    document.getElementById('tab-' + tab).style.display = 'block';
    document.getElementById('menu-' + tab).classList.add('active');
}
</script>
@endpush
