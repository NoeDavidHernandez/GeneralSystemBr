@extends('layouts.admin')

@section('title', 'Servicios')

@push('styles')
<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        animation: fadeUp 0.3s ease forwards;
        opacity: 0;
    }
    .page-title h1 {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--text-primary);
    }
    .page-title p {
        color: var(--text-secondary);
        margin-top: 4px;
        font-size: 0.95rem;
    }
    .btn-new {
        background: var(--accent-gradient);
        color: var(--accent-btn-text);
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }
    .btn-new:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }
    
    .table-container {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 24px;
        box-shadow: var(--shadow-sm);
        animation: fadeUp 0.4s ease forwards;
        opacity: 0;
        overflow-x: auto;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
    }
    th {
        text-align: left;
        padding: 12px 16px;
        color: var(--text-secondary);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-size: 0.75rem;
        border-bottom: 1px solid var(--border-color);
    }
    td {
        padding: 16px;
        color: var(--text-primary);
        border-bottom: 1px solid var(--border-color);
        vertical-align: middle;
    }
    tr:last-child td {
        border-bottom: none;
    }
    tr:hover {
        background: var(--bg-card-hover);
    }
    .badge {
        padding: 4px 10px;
        border-radius: 100px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .badge-active { background: rgba(16, 185, 129, 0.1); color: var(--green); }
    .badge-inactive { background: rgba(239, 68, 68, 0.1); color: var(--red); }
    
    .action-btn {
        background: transparent;
        border: none;
        color: var(--text-secondary);
        cursor: pointer;
        padding: 6px;
        border-radius: 6px;
        transition: all 0.2s;
        margin-right: 4px;
    }
    .action-btn:hover { background: var(--bg-primary); color: var(--accent); }
    .action-btn.delete:hover { color: var(--red); }

    .modal-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);
        display: none; align-items: center; justify-content: center; z-index: 1000;
        opacity: 0; transition: opacity 0.3s;
    }
    .modal-overlay.show { display: flex; opacity: 1; }
    .modal {
        background: var(--bg-card); width: 90%; max-width: 500px; border-radius: 16px;
        border: 1px solid var(--border-color); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.3);
        transform: translateY(20px); transition: transform 0.3s;
    }
    .modal-overlay.show .modal { transform: translateY(0); }
    .modal-header { padding: 20px 24px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
    .modal-header h3 { font-size: 1.1rem; font-weight: 700; color: var(--text-primary); }
    .btn-close { background: transparent; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.2rem; }
    .modal-body { padding: 24px; }
    .form-group { margin-bottom: 16px; }
    .form-label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 8px; text-transform: uppercase; }
    .form-control { width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-primary); color: var(--text-primary); }
    .form-control:focus { outline: none; border-color: var(--accent); }
    .btn-submit { width: 100%; padding: 12px; background: var(--accent-gradient); color: var(--accent-btn-text); border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }

    @keyframes fadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
</style>
@endpush

@section('content')
<div class="page-header">
    <div class="page-title">
        <h1>Catálogo de Servicios</h1>
        <p>Gestiona los cortes, barbas y tratamientos que ofreces</p>
    </div>
    <button class="btn-new" onclick="openModal('new')"><i data-lucide="plus"></i> Nuevo Servicio</button>
</div>

@if(session('success'))
    <div style="background: rgba(16, 185, 129, 0.1); color: #059669; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 500;">
        {{ session('success') }}
    </div>
@endif

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Categoría</th>
                <th>Nombre del Servicio</th>
                <th>Precio</th>
                <th>Duración</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($servicios as $s)
            <tr>
                <td><strong>{{ $s->categoria }}</strong></td>
                <td>
                    {{ $s->nombre }}
                    @if($s->precio_variable)
                        <br><span style="font-size: 0.75rem; color: var(--text-secondary);">El precio puede variar</span>
                    @endif
                </td>
                <td>${{ number_format($s->precio, 2) }}</td>
                <td>{{ $s->duracion_min }} min</td>
                <td>
                    @if($s->activo)
                        <span class="badge badge-active">Activo</span>
                    @else
                        <span class="badge badge-inactive">Inactivo</span>
                    @endif
                </td>
                <td>
                    <button class="action-btn" onclick="openModal('edit', {{ $s }})"><i data-lucide="edit-2" style="width:18px;"></i></button>
                    @if($s->activo)
                    <form action="{{ route('admin.servicios.destroy', $s->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('¿Desactivar este servicio?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="action-btn delete"><i data-lucide="trash-2" style="width:18px;"></i></button>
                    </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align:center; color: var(--text-secondary); padding: 32px;">
                    <i data-lucide="scissors" style="width: 48px; height: 48px; margin-bottom: 12px; opacity: 0.5;"></i><br>
                    No has agregado ningún servicio aún.<br>Haz clic en "Nuevo Servicio" para empezar.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Modal -->
<div class="modal-overlay" id="servicioModal">
    <div class="modal">
        <div class="modal-header">
            <h3 id="modalTitle">Nuevo Servicio</h3>
            <button class="btn-close" onclick="closeModal()"><i data-lucide="x"></i></button>
        </div>
        <div class="modal-body">
            <form id="servicioForm" action="{{ route('admin.servicios.store') }}" method="POST">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                
                <div class="form-group">
                    <label class="form-label">Categoría</label>
                    <input type="text" name="categoria" id="categoria" class="form-control" placeholder="Ej: Cortes, Barbas, Faciales..." required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nombre del Servicio</label>
                    <input type="text" name="nombre" id="nombre" class="form-control" placeholder="Ej: Corte Clásico" required>
                </div>

                <div style="display:flex; gap:16px;">
                    <div class="form-group" style="flex:1;">
                        <label class="form-label">Precio ($)</label>
                        <input type="number" step="0.01" name="precio" id="precio" class="form-control" required>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label class="form-label">Duración (minutos)</label>
                        <input type="number" name="duracion_min" id="duracion_min" class="form-control" placeholder="Ej: 30" required>
                    </div>
                </div>

                <div class="form-group" style="display: flex; gap: 8px; align-items: center; margin-bottom: 24px;">
                    <input type="checkbox" name="precio_variable" id="precio_variable" value="1">
                    <label for="precio_variable" style="font-size:0.85rem; color:var(--text-secondary); cursor:pointer;">
                        El precio final puede variar según el cliente/barbero
                    </label>
                </div>

                <!-- Oculto para crear, visible para editar -->
                <div class="form-group" id="groupActivo" style="display:none; gap: 8px; align-items: center; margin-bottom: 24px;">
                    <input type="checkbox" name="activo" id="activo" value="1">
                    <label for="activo" style="font-size:0.85rem; color:var(--text-secondary); cursor:pointer;">
                        Servicio Activo
                    </label>
                </div>

                <button type="submit" class="btn-submit">Guardar Servicio</button>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function openModal(mode, data = null) {
        const modal = document.getElementById('servicioModal');
        const form = document.getElementById('servicioForm');
        
        modal.classList.add('show');
        
        if (mode === 'edit') {
            document.getElementById('modalTitle').innerText = 'Editar Servicio';
            form.action = `/admin/servicios/${data.id}`;
            document.getElementById('formMethod').value = 'PUT';
            
            document.getElementById('categoria').value = data.categoria;
            document.getElementById('nombre').value = data.nombre;
            document.getElementById('precio').value = data.precio;
            document.getElementById('duracion_min').value = data.duracion_min;
            document.getElementById('precio_variable').checked = data.precio_variable == 1;
            
            document.getElementById('groupActivo').style.display = 'flex';
            document.getElementById('activo').checked = data.activo == 1;
        } else {
            document.getElementById('modalTitle').innerText = 'Nuevo Servicio';
            form.action = "{{ route('admin.servicios.store') }}";
            document.getElementById('formMethod').value = 'POST';
            form.reset();
            document.getElementById('groupActivo').style.display = 'none';
        }
    }
    
    function closeModal() {
        const modal = document.getElementById('servicioModal');
        modal.classList.remove('show');
    }

    // Cerrar si se da clic fuera del modal
    window.onclick = function(event) {
        const modal = document.getElementById('servicioModal');
        if (event.target == modal) {
            closeModal();
        }
    }
</script>
@endpush
