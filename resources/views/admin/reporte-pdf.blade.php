<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Barbería — {{ $periodo }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: #1a1a2e; line-height: 1.5; }

        .header {
            background: linear-gradient(135deg, #1a1a2e, #2d2d44);
            color: #fff;
            padding: 30px;
            text-align: center;
            margin-bottom: 24px;
        }
        .header h1 { font-size: 22px; margin-bottom: 4px; }
        .header p { font-size: 12px; color: #ccc; }
        .header .periodo { font-size: 14px; color: #f0d48a; margin-top: 8px; font-weight: 600; }

        .section { margin: 0 24px 24px; }
        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: #1a1a2e;
            border-bottom: 2px solid #d4a853;
            padding-bottom: 6px;
            margin-bottom: 12px;
        }

        /* KPI Cards */
        .kpis-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .kpi-row { display: table-row; }
        .kpi-cell {
            display: table-cell;
            width: 20%;
            text-align: center;
            padding: 12px 8px;
            border: 1px solid #e5e5e5;
            background: #fafafa;
        }
        .kpi-cell .value { font-size: 20px; font-weight: 800; color: #1a1a2e; }
        .kpi-cell .label { font-size: 9px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }

        /* Tables */
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th {
            background: #2d2d44;
            color: #fff;
            padding: 8px 12px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
        }
        td {
            padding: 7px 12px;
            border-bottom: 1px solid #eee;
            font-size: 11px;
        }
        tr:nth-child(even) td { background: #f9f9f9; }
        tr:hover td { background: #f0f0f0; }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: 700; }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
        }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .badge-blue { background: #dbeafe; color: #1e40af; }
        .badge-orange { background: #ffedd5; color: #9a3412; }
        .badge-purple { background: #ede9fe; color: #5b21b6; }

        .footer {
            margin-top: 30px;
            padding: 16px 24px;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #eee;
        }

        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>💈 Reporte de Barbería</h1>
        <p>Panel de Administración</p>
        <div class="periodo">{{ $periodo }} — {{ $fecha_inicio }} al {{ $fecha_fin }}</div>
    </div>

    <!-- KPIs -->
    <div class="section">
        <div class="section-title">📊 Resumen General</div>
        <div class="kpis-grid">
            <div class="kpi-row">
                <div class="kpi-cell">
                    <div class="value">${{ number_format($kpis['ingresos'], 0) }}</div>
                    <div class="label">Ingresos</div>
                </div>
                <div class="kpi-cell">
                    <div class="value">{{ $kpis['total_citas'] }}</div>
                    <div class="label">Total Citas</div>
                </div>
                <div class="kpi-cell">
                    <div class="value">{{ $kpis['completadas'] }}</div>
                    <div class="label">Completadas</div>
                </div>
                <div class="kpi-cell">
                    <div class="value">{{ $kpis['clientes_nuevos'] }}</div>
                    <div class="label">Clientes Nuevos</div>
                </div>
                <div class="kpi-cell">
                    <div class="value">{{ $kpis['tasa_cancelacion'] }}%</div>
                    <div class="label">Tasa Cancelación</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Servicios más solicitados -->
    <div class="section">
        <div class="section-title">✂️ Servicios Más Solicitados</div>
        @if(count($servicios_populares['labels']) > 0)
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Servicio</th>
                    <th class="text-right">Total Citas</th>
                </tr>
            </thead>
            <tbody>
                @foreach($servicios_populares['labels'] as $i => $nombre)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="font-bold">{{ $nombre }}</td>
                    <td class="text-right">{{ $servicios_populares['data'][$i] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p style="color: #999; text-align: center; padding: 16px;">Sin datos en este periodo</p>
        @endif
    </div>

    <!-- Estado de citas -->
    <div class="section">
        <div class="section-title">📋 Estado de Citas</div>
        @if(count($estados_citas['labels']) > 0)
        <table>
            <thead>
                <tr>
                    <th>Estado</th>
                    <th class="text-right">Cantidad</th>
                    <th class="text-right">Porcentaje</th>
                </tr>
            </thead>
            <tbody>
                @php $totalEstados = array_sum($estados_citas['data']); @endphp
                @foreach($estados_citas['labels'] as $i => $estado)
                <tr>
                    <td>
                        @php
                            $badgeClass = match($estado) {
                                'Completada' => 'badge-green',
                                'Cancelada' => 'badge-red',
                                'Pendiente' => 'badge-orange',
                                'Confirmada' => 'badge-blue',
                                'No asistió' => 'badge-purple',
                                default => 'badge-blue',
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }}">{{ $estado }}</span>
                    </td>
                    <td class="text-right">{{ $estados_citas['data'][$i] }}</td>
                    <td class="text-right">{{ $totalEstados > 0 ? round(($estados_citas['data'][$i] / $totalEstados) * 100, 1) : 0 }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p style="color: #999; text-align: center; padding: 16px;">Sin datos en este periodo</p>
        @endif
    </div>

    <!-- Horas pico -->
    <div class="section">
        <div class="section-title">⏰ Horas Pico</div>
        @if(count($horas_pico['labels']) > 0)
        <table>
            <thead>
                <tr>
                    <th>Hora</th>
                    <th class="text-right">Citas</th>
                </tr>
            </thead>
            <tbody>
                @foreach($horas_pico['labels'] as $i => $hora)
                <tr>
                    <td>{{ $hora }}</td>
                    <td class="text-right">{{ $horas_pico['data'][$i] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p style="color: #999; text-align: center; padding: 16px;">Sin datos en este periodo</p>
        @endif
    </div>

    <!-- Top clientes -->
    <div class="section">
        <div class="section-title">🏆 Top Clientes</div>
        @if(count($top_clientes) > 0)
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cliente</th>
                    <th class="text-right">Visitas</th>
                    <th class="text-right">Gasto Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($top_clientes as $i => $cliente)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="font-bold">{{ $cliente['nombre'] }}</td>
                    <td class="text-right">{{ $cliente['visitas'] }}</td>
                    <td class="text-right">${{ number_format($cliente['gasto'], 0) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p style="color: #999; text-align: center; padding: 16px;">Sin datos en este periodo</p>
        @endif
    </div>

    <!-- Footer -->
    <div class="footer">
        Reporte generado automáticamente el {{ now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY [a las] h:mm A') }}
    </div>
</body>
</html>
