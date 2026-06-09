@extends('layouts.admin')

@section('title', 'Finanzas')

@push('styles')
    <style>
        .page-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; animation: fadeUp 0.3s ease forwards; opacity: 0; }
        .page-title h1 { font-size: 1.8rem; font-weight: 700; color: var(--text-primary); }
        .page-title p { color: var(--text-secondary); margin-top: 4px; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; }
        
        .finanzas-resumen { background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border-color); padding: 24px; box-shadow: var(--shadow-sm); margin-bottom: 24px; animation: fadeUp 0.4s ease forwards; opacity: 0; }
        .resumen-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .monto-total { font-size: 2rem; font-weight: 800; color: var(--text-primary); line-height: 1; display: flex; align-items: baseline; gap: 8px; }
        .monto-total span { font-size: 0.9rem; font-weight: 500; color: var(--text-secondary); }
        
        .progress-bar-container { height: 12px; width: 100%; background: var(--border-color); border-radius: 100px; display: flex; overflow: hidden; margin-bottom: 24px; }
        .progress-segment { height: 100%; transition: width 0.3s ease; }
        
        .metodos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; }
        .metodo-card { border: 1px solid var(--border-color); border-radius: 12px; padding: 16px; display: flex; flex-direction: column; gap: 12px; transition: transform 0.2s, box-shadow 0.2s; }
        .metodo-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .metodo-header { display: flex; align-items: center; gap: 10px; font-weight: 600; color: var(--text-primary); font-size: 0.9rem; }
        .metodo-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1rem; color: #fff; }
        .metodo-monto { font-size: 1.3rem; font-weight: 700; color: var(--text-primary); }
        
        .movimientos-panel { background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); animation: fadeUp 0.5s ease forwards; opacity: 0; overflow: hidden; }
        .panel-header { padding: 16px 24px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
        .panel-header h2 { font-size: 1.1rem; font-weight: 700; color: var(--text-primary); }
        
        .table-wrap { overflow-x: auto; }
        table.movimientos-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        table.movimientos-table thead th { background: var(--bg-card-hover); color: var(--text-secondary); font-weight: 600; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em; padding: 14px 24px; text-align: left; border-bottom: 1px solid var(--border-color); white-space: nowrap; }
        table.movimientos-table tbody tr { transition: background 0.2s; }
        table.movimientos-table tbody tr:hover { background: var(--bg-card-hover); }
        table.movimientos-table tbody td { padding: 16px 24px; border-bottom: 1px solid var(--border-color); vertical-align: middle; color: var(--text-primary); }
        table.movimientos-table tbody tr:last-child td { border-bottom: none; }
        
        .badge { display: inline-block; padding: 4px 10px; border-radius: 100px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.02em; }
        .badge-ingreso { background: rgba(16, 185, 129, 0.1); color: #059669; }
        .badge-egreso { background: rgba(239, 68, 68, 0.1); color: #dc2626; }
        
        .monto-positivo { color: #059669; font-weight: 700; }
        .monto-negativo { color: #dc2626; font-weight: 700; }

        @keyframes fadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
    </style>
@endpush

@section('content')
<div class="page-header">
    <div class="page-title">
        <h1>Finanzas</h1>
        <p>Resumen del Mes: {{ \Carbon\Carbon::now()->translatedFormat('F Y') }}</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="abrirModal(null)"><i data-lucide="plus"></i> Nuevo Movimiento</button>
    </div>
</div>

@if(session('success'))
    <div style="background: rgba(16, 185, 129, 0.1); color: #059669; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 500;">
        {{ session('success') }}
    </div>
@endif

<div class="finanzas-resumen">
    <div class="resumen-header">
        <div>
            <div style="font-size: 0.8rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">Ingresos Brutos</div>
            <div class="monto-total">${{ number_format($totalIngresos, 2) }} <span>({{ $ingresosPorMetodo->sum('tx_count') }} transacciones)</span></div>
        </div>
        <div style="text-align: right;">
            <div style="font-size: 0.8rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">Egresos</div>
            <div class="monto-total" style="color: var(--red);">${{ number_format($totalEgresos, 2) }}</div>
        </div>
    </div>
    
    @php
        $colores = [
            'Efectivo' => '#10b981',
            'Transferencia' => '#f59e0b',
            'Tarjeta' => '#3b82f6',
            'Zelle' => '#8b5cf6',
            'Otros' => '#64748b'
        ];
    @endphp

    <div class="progress-bar-container">
        @foreach($ingresosPorMetodo as $metodo)
            @php 
                $porcentaje = $totalIngresos > 0 ? ($metodo->total / $totalIngresos) * 100 : 0; 
                $color = $colores[$metodo->metodo_pago] ?? $colores['Otros'];
            @endphp
            <div class="progress-segment" style="width: {{ $porcentaje }}%; background-color: {{ $color }};" title="{{ $metodo->metodo_pago }}: {{ number_format($porcentaje, 1) }}%"></div>
        @endforeach
    </div>

    <div class="metodos-grid">
        @foreach($ingresosPorMetodo as $metodo)
            @php 
                $color = $colores[$metodo->metodo_pago] ?? $colores['Otros'];
                $porcentaje = $totalIngresos > 0 ? ($metodo->total / $totalIngresos) * 100 : 0; 
            @endphp
            <div class="metodo-card">
                <div class="metodo-header">
                    <div class="metodo-icon" style="background-color: {{ $color }};"><i data-lucide="wallet"></i></div>
                    {{ $metodo->metodo_pago }}
                </div>
                <div class="metodo-monto">${{ number_format($metodo->total, 2) }}</div>
                <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--text-secondary);">
                    <span>{{ $metodo->tx_count }} tx</span>
                    <span>{{ number_format($porcentaje, 1) }}%</span>
                </div>
                <div style="width: 100%; height: 4px; background: var(--bg-primary); border-radius: 4px; overflow: hidden;">
                    <div style="height: 100%; background: {{ $color }}; width: {{ $porcentaje }}%;"></div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="movimientos-panel">
    <div class="panel-header">
        <h2>Movimientos Recientes</h2>
    </div>
    <div class="table-wrap">
        <table class="movimientos-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Concepto</th>
                    <th>Cliente / Proveedor</th>
                    <th>Método</th>
                    <th>Tipo</th>
                    <th style="text-align: right;">Monto</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movimientos as $mov)
                <tr>
                    <td style="color: var(--text-secondary); font-weight: 500;">
                        {{ \Carbon\Carbon::parse($mov->fecha)->format('d M • H:i') }}
                    </td>
                    <td style="font-weight: 600;">{{ $mov->concepto }}</td>
                    <td style="color: var(--text-secondary);">{{ $mov->persona ?? '-' }}</td>
                    <td>{{ $mov->metodo_pago }}</td>
                    <td>
                        <span class="badge {{ $mov->tipo === 'ingreso' ? 'badge-ingreso' : 'badge-egreso' }}">
                            {{ $mov->tipo }}
                        </span>
                    </td>
                    <td style="text-align: right;" class="{{ $mov->tipo === 'ingreso' ? 'monto-positivo' : 'monto-negativo' }}">
                        {{ $mov->tipo === 'ingreso' ? '+' : '-' }}${{ number_format($mov->monto, 2) }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 48px; color: var(--text-secondary);">
                        <i data-lucide="calculator" style="width: 48px; height: 48px; margin-bottom: 12px; opacity: 0.5;"></i>
                        <p style="font-size: 1.1rem; font-weight: 500;">No hay movimientos registrados</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('modals')
<!-- Modal Movimiento -->
<div class="modal-overlay" id="modal-movimiento">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title" id="modal-title">Registrar Movimiento</div>
            <button class="modal-close" onclick="cerrarModal()"><i data-lucide="x"></i></button>
        </div>
        <form id="form-movimiento" method="POST" action="{{ route('admin.finanzas.store') }}">
            @csrf
            
            <div style="display: flex; gap: 16px; margin-bottom: 16px;">
                <label style="flex: 1; cursor: pointer; border: 1px solid var(--border-color); padding: 12px; border-radius: 8px; text-align: center; font-weight: 600;" id="lbl-ingreso" onclick="setTipo('ingreso')">
                    <input type="radio" name="tipo" value="ingreso" style="display:none;" id="rad-ingreso" checked>
                    <span style="color: #059669;">Ingreso</span>
                </label>
                <label style="flex: 1; cursor: pointer; border: 1px solid var(--border-color); padding: 12px; border-radius: 8px; text-align: center; font-weight: 600;" id="lbl-egreso" onclick="setTipo('egreso')">
                    <input type="radio" name="tipo" value="egreso" style="display:none;" id="rad-egreso">
                    <span style="color: #dc2626;">Egreso</span>
                </label>
            </div>
            
            <div class="form-group">
                <label class="form-label">Monto ($) *</label>
                <input type="number" step="0.01" name="monto" class="form-control" required placeholder="0.00">
            </div>

            <div class="form-group">
                <label class="form-label">Concepto *</label>
                <input type="text" name="concepto" class="form-control" required placeholder="Ej. Corte de Cabello, Compra de productos...">
            </div>

            <div class="form-group">
                <label class="form-label">Método de Pago *</label>
                <select name="metodo_pago" class="form-control" required>
                    <option value="Efectivo">Efectivo</option>
                    <option value="Transferencia">Transferencia</option>
                    <option value="Tarjeta">Tarjeta / Punto de Venta</option>
                    <option value="Zelle">Zelle</option>
                    <option value="Otros">Otros</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Cliente / Proveedor</label>
                <input type="text" name="persona" class="form-control" placeholder="Opcional">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 24px;">
                Guardar Movimiento
            </button>
        </form>
    </div>
</div>
@endpush

@push('scripts-bottom')
<script>
    function setTipo(tipo) {
        document.getElementById('lbl-ingreso').style.borderColor = tipo === 'ingreso' ? '#059669' : 'var(--border-color)';
        document.getElementById('lbl-ingreso').style.background = tipo === 'ingreso' ? 'rgba(16, 185, 129, 0.05)' : 'transparent';
        
        document.getElementById('lbl-egreso').style.borderColor = tipo === 'egreso' ? '#dc2626' : 'var(--border-color)';
        document.getElementById('lbl-egreso').style.background = tipo === 'egreso' ? 'rgba(239, 68, 68, 0.05)' : 'transparent';
    }

    function abrirModal() {
        document.getElementById('modal-movimiento').classList.add('active');
        setTipo('ingreso');
    }
    
    function cerrarModal() {
        document.getElementById('modal-movimiento').classList.remove('active');
    }
    
    // Iniciar con UI correcta
    setTipo('ingreso');
</script>
@endpush
