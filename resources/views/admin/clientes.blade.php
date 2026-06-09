@extends('layouts.admin')

@section('title', 'Directorio de Clientes')

@push('styles')
    <style>
        .page-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; animation: fadeUp 0.3s ease forwards; opacity: 0; }
        .page-title h1 { font-size: 1.8rem; font-weight: 700; color: var(--text-primary); }
        .page-title p { color: var(--text-secondary); margin-top: 4px; font-size: 0.95rem; }
        
        .clientes-panel { background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); animation: fadeUp 0.4s ease forwards; opacity: 0; overflow: hidden; }
        .table-toolbar { padding: 16px 24px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
        
        .search-box { position: relative; width: 300px; }
        .search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-secondary); width: 16px; height: 16px; }
        .search-box input { width: 100%; padding: 10px 10px 10px 36px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-primary); color: var(--text-primary); font-family: 'Inter', sans-serif; font-size: 0.9rem; transition: border-color 0.2s; }
        .search-box input:focus { outline: none; border-color: var(--accent); }

        .clientes-table-wrap { overflow-x: auto; }
        table.clientes-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        table.clientes-table thead th { background: var(--bg-card-hover); color: var(--text-secondary); font-weight: 600; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; padding: 14px 24px; text-align: left; border-bottom: 1px solid var(--border-color); white-space: nowrap; }
        table.clientes-table tbody tr { transition: background 0.2s; }
        table.clientes-table tbody tr:hover { background: var(--bg-card-hover); }
        table.clientes-table tbody td { padding: 16px 24px; border-bottom: 1px solid var(--border-color); vertical-align: middle; color: var(--text-primary); }
        table.clientes-table tbody tr:last-child td { border-bottom: none; }
        
        .cliente-info { display: flex; align-items: center; gap: 12px; }
        .cliente-avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--sidebar-active-bg); color: var(--accent); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.1rem; flex-shrink: 0; }
        .cliente-info-text strong { display: block; font-weight: 600; color: var(--text-primary); font-size: 0.95rem; margin-bottom: 2px; }
        .cliente-info-text span { font-size: 0.8rem; color: var(--text-secondary); }
        
        .badge { display: inline-block; padding: 4px 10px; border-radius: 100px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.02em; }
        .badge-frecuente { background: rgba(16, 185, 129, 0.1); color: #059669; }
        .badge-nuevo { background: rgba(59, 130, 246, 0.1); color: #2563eb; }
        .badge-bloqueado { background: rgba(239, 68, 68, 0.1); color: #dc2626; }
        
        .btn-icon { background: none; border: none; color: var(--text-secondary); cursor: pointer; padding: 6px; border-radius: 6px; transition: all 0.2s; display: inline-flex; align-items: center; justify-content: center; }
        .btn-icon:hover { background: var(--border-color); color: var(--text-primary); }

        @keyframes fadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
    </style>
@endpush

@section('content')
<div class="page-header">
    <div class="page-title">
        <h1>Directorio de Clientes</h1>
        <p>Administra los clientes que han interactuado con tu negocio</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="abrirModal(null)"><i data-lucide="plus"></i> Nuevo Cliente</button>
    </div>
</div>

@if(session('success'))
    <div style="background: rgba(16, 185, 129, 0.1); color: #059669; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 500;">
        {{ session('success') }}
    </div>
@endif

<div class="clientes-panel">
    <div class="table-toolbar">
        <div class="search-box">
            <i data-lucide="search"></i>
            <input type="text" id="buscador-clientes" placeholder="Buscar por nombre o teléfono..." onkeyup="filtrarTabla()">
        </div>
        <div style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 500;">
            Total: {{ $clientes->count() }} clientes
        </div>
    </div>
    <div class="clientes-table-wrap">
        <table class="clientes-table" id="tabla-clientes">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Visitas Completadas</th>
                    <th>Última Visita</th>
                    <th>Etiqueta</th>
                    <th style="text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($clientes as $cliente)
                <tr>
                    <td>
                        <div class="cliente-info">
                            <div class="cliente-avatar">{{ strtoupper(substr($cliente->nombre, 0, 1)) }}</div>
                            <div class="cliente-info-text">
                                <strong>{{ $cliente->nombre }}</strong>
                                <span><i data-lucide="phone" style="width:12px; height:12px; display:inline-block; vertical-align:middle; margin-right:2px;"></i> {{ $cliente->telefono }}</span>
                            </div>
                        </div>
                    </td>
                    <td style="font-weight: 600; color: var(--text-primary);">
                        {{ $cliente->visitas_completadas ?? 0 }}
                    </td>
                    <td>
                        @if($cliente->citas && $cliente->citas->count() > 0)
                            @php $ultimaCita = $cliente->citas->first(); @endphp
                            <div style="font-weight: 500; color: var(--text-primary);">{{ \Carbon\Carbon::parse($ultimaCita->fecha)->format('d/m/Y') }}</div>
                            <div style="font-size: 0.75rem; color: var(--text-secondary);">Estado: {{ ucfirst($ultimaCita->estado) }}</div>
                        @else
                            <span style="color: var(--text-secondary); font-size: 0.85rem;">Sin registro</span>
                        @endif
                    </td>
                    <td>
                        @if($cliente->bloqueado)
                            <span class="badge badge-bloqueado">Bloqueado</span>
                        @elseif($cliente->visitas_completadas >= 3)
                            <span class="badge badge-frecuente">Frecuente</span>
                        @else
                            <span class="badge badge-nuevo">Nuevo</span>
                        @endif
                    </td>
                    <td style="text-align: right;">
                        <button class="btn-icon" title="Editar / Ver Notas" onclick="abrirModal({{ $cliente }})">
                            <i data-lucide="edit-2" style="width: 18px; height: 18px;"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 48px; color: var(--text-secondary);">
                        <i data-lucide="users" style="width: 48px; height: 48px; margin-bottom: 12px; opacity: 0.5;"></i>
                        <p style="font-size: 1.1rem; font-weight: 500;">Aún no hay clientes registrados</p>
                        <p style="font-size: 0.85rem; margin-top: 4px;">Los clientes aparecerán aquí automáticamente al agendar citas.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('modals')
<!-- Modal Cliente -->
<div class="modal-overlay" id="modal-cliente">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title" id="modal-title">Nuevo Cliente</div>
            <button class="modal-close" onclick="cerrarModal()"><i data-lucide="x"></i></button>
        </div>
        <form id="form-cliente" method="POST" action="{{ route('admin.clientes.store') }}">
            @csrf
            <input type="hidden" name="_method" id="form-method" value="POST">
            
            <div class="form-group">
                <label class="form-label">Nombre del Cliente *</label>
                <input type="text" name="nombre" id="cli_nombre" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Teléfono (WhatsApp) *</label>
                <input type="text" name="telefono" id="cli_telefono" class="form-control" required placeholder="+52...">
            </div>
            
            <div class="form-group">
                <label class="form-label">Notas Internas</label>
                <textarea name="notas" id="cli_notas" class="form-control" rows="3" placeholder="Información relevante sobre el cliente, preferencias de corte, etc."></textarea>
            </div>
            
            <div class="form-group" id="group-bloqueado" style="display: none; margin-top: 24px;">
                <label class="checkbox-label" style="font-weight: 600; color: var(--red);">
                    <input type="checkbox" name="bloqueado" id="cli_bloqueado" value="1">
                    Bloquear Cliente (No podrá agendar más citas)
                </label>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 24px;">
                Guardar Cliente
            </button>
        </form>
    </div>
</div>
@endpush

@push('scripts-bottom')
<script>
    function abrirModal(cliente = null) {
        const form = document.getElementById('form-cliente');
        const modalTitle = document.getElementById('modal-title');
        const methodInput = document.getElementById('form-method');
        const groupBloqueado = document.getElementById('group-bloqueado');
        
        if (cliente) {
            modalTitle.textContent = 'Editar Cliente';
            form.action = `/admin/clientes/${cliente.id}`;
            methodInput.value = 'PUT';
            
            document.getElementById('cli_nombre').value = cliente.nombre;
            document.getElementById('cli_telefono').value = cliente.telefono;
            document.getElementById('cli_notas').value = cliente.notas || '';
            
            groupBloqueado.style.display = 'block';
            document.getElementById('cli_bloqueado').checked = cliente.bloqueado;
        } else {
            modalTitle.textContent = 'Nuevo Cliente';
            form.action = `{{ route('admin.clientes.store') }}`;
            methodInput.value = 'POST';
            
            document.getElementById('cli_nombre').value = '';
            document.getElementById('cli_telefono').value = '';
            document.getElementById('cli_notas').value = '';
            
            groupBloqueado.style.display = 'none';
        }
        
        document.getElementById('modal-cliente').classList.add('active');
    }
    
    function cerrarModal() {
        document.getElementById('modal-cliente').classList.remove('active');
    }
    
    // Buscador en tabla
    function filtrarTabla() {
        let input = document.getElementById("buscador-clientes");
        let filter = input.value.toUpperCase();
        let table = document.getElementById("tabla-clientes");
        let tr = table.getElementsByTagName("tr");

        for (let i = 1; i < tr.length; i++) {
            let tdName = tr[i].getElementsByTagName("td")[0];
            if (tdName) {
                let txtValue = tdName.textContent || tdName.innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }
    }
</script>
@endpush
