<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Servicio;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class AdminDashboardController extends Controller
{
    // ─── Vista principal ──────────────────────────────────────────────────

    public function index()
    {
        return view('admin.dashboard');
    }

    // ─── API de datos para gráficas ───────────────────────────────────────

    public function datos(Request $request): JsonResponse
    {
        $periodo = $request->get('periodo', '1m');
        [$fechaInicio, $fechaFin] = $this->rangoFechas($periodo);

        return response()->json([
            'periodo'            => $periodo,
            'fecha_inicio'       => $fechaInicio->toDateString(),
            'fecha_fin'          => $fechaFin->toDateString(),
            'kpis'               => $this->calcularKpis($fechaInicio, $fechaFin),
            'ingresos_por_dia'   => $this->ingresosPorDia($fechaInicio, $fechaFin),
            'servicios_populares'=> $this->serviciosPopulares($fechaInicio, $fechaFin),
            'estados_citas'      => $this->estadosCitas($fechaInicio, $fechaFin),
            'clientes_nuevos_vs_recurrentes' => $this->clientesNuevosVsRecurrentes($fechaInicio, $fechaFin),
            'horas_pico'         => $this->horasPico($fechaInicio, $fechaFin),
            'tendencia_citas'    => $this->tendenciaCitas($fechaInicio, $fechaFin, $periodo),
        ]);
    }

    // ─── Exportar PDF ─────────────────────────────────────────────────────

    public function exportarPdf(Request $request)
    {
        $periodo = $request->get('periodo', '1m');
        [$fechaInicio, $fechaFin] = $this->rangoFechas($periodo);

        $data = [
            'periodo'             => $this->periodoTexto($periodo),
            'fecha_inicio'        => $fechaInicio->locale('es')->isoFormat('D [de] MMMM [de] YYYY'),
            'fecha_fin'           => $fechaFin->locale('es')->isoFormat('D [de] MMMM [de] YYYY'),
            'kpis'                => $this->calcularKpis($fechaInicio, $fechaFin),
            'servicios_populares' => $this->serviciosPopulares($fechaInicio, $fechaFin),
            'estados_citas'       => $this->estadosCitas($fechaInicio, $fechaFin),
            'ingresos_por_dia'    => $this->ingresosPorDia($fechaInicio, $fechaFin),
            'horas_pico'          => $this->horasPico($fechaInicio, $fechaFin),
            'top_clientes'        => $this->topClientes($fechaInicio, $fechaFin),
        ];

        $pdf = Pdf::loadView('admin.reporte-pdf', $data);
        $pdf->setPaper('letter', 'portrait');

        $nombreArchivo = 'reporte_barberia_' . $fechaInicio->format('Ymd') . '_' . $fechaFin->format('Ymd') . '.pdf';

        return $pdf->download($nombreArchivo);
    }

    // ─── Cálculos privados ────────────────────────────────────────────────

    private function rangoFechas(string $periodo): array
    {
        $fin = Carbon::now();

        $inicio = match ($periodo) {
            '1w' => Carbon::now()->subWeek(),
            '1m' => Carbon::now()->subMonth(),
            '3m' => Carbon::now()->subMonths(3),
            '6m' => Carbon::now()->subMonths(6),
            '1y' => Carbon::now()->subYear(),
            default => Carbon::now()->subMonth(),
        };

        return [$inicio->startOfDay(), $fin->endOfDay()];
    }

    private function periodoTexto(string $periodo): string
    {
        return match ($periodo) {
            '1w' => 'Última semana',
            '1m' => 'Último mes',
            '3m' => 'Últimos 3 meses',
            '6m' => 'Últimos 6 meses',
            '1y' => 'Último año',
            default => 'Último mes',
        };
    }

    private function calcularKpis(Carbon $inicio, Carbon $fin): array
    {
        $citas = Cita::whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()]);

        $totalCitas      = (clone $citas)->count();
        $completadas     = (clone $citas)->where('estado', 'completada')->count();
        $canceladas      = (clone $citas)->where('estado', 'cancelada')->count();
        $noAsistio       = (clone $citas)->where('estado', 'no_asistio')->count();

        // Ingresos: usar precio_cobrado si existe, sino el precio del servicio
        $ingresos = Cita::whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->where('estado', 'completada')
            ->get()
            ->sum(function ($cita) {
                return $cita->precio_cobrado ?? optional($cita->servicio)->precio ?? 0;
            });

        $clientesNuevos = Cliente::whereBetween('created_at', [$inicio, $fin])->count();

        $tasaCancelacion = $totalCitas > 0
            ? round((($canceladas + $noAsistio) / $totalCitas) * 100, 1)
            : 0;

        return [
            'total_citas'       => $totalCitas,
            'completadas'       => $completadas,
            'canceladas'        => $canceladas,
            'no_asistio'        => $noAsistio,
            'ingresos'          => round($ingresos, 2),
            'clientes_nuevos'   => $clientesNuevos,
            'tasa_cancelacion'  => $tasaCancelacion,
        ];
    }

    private function ingresosPorDia(Carbon $inicio, Carbon $fin): array
    {
        $citas = Cita::whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->where('estado', 'completada')
            ->with('servicio')
            ->orderBy('fecha')
            ->get()
            ->groupBy(fn($cita) => $cita->fecha->format('Y-m-d'));

        $labels = [];
        $data   = [];

        foreach ($citas as $fecha => $grupo) {
            $labels[] = Carbon::parse($fecha)->locale('es')->isoFormat('D MMM');
            $data[]   = round($grupo->sum(fn($c) => $c->precio_cobrado ?? optional($c->servicio)->precio ?? 0), 2);
        }

        return compact('labels', 'data');
    }

    private function serviciosPopulares(Carbon $inicio, Carbon $fin): array
    {
        $servicios = Cita::whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->whereNotIn('estado', ['cancelada'])
            ->selectRaw('servicio_id, COUNT(*) as total')
            ->groupBy('servicio_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $labels = [];
        $data   = [];

        foreach ($servicios as $s) {
            $servicio = Servicio::find($s->servicio_id);
            $labels[] = $servicio ? $servicio->nombre : "Servicio #{$s->servicio_id}";
            $data[]   = $s->total;
        }

        return compact('labels', 'data');
    }

    private function estadosCitas(Carbon $inicio, Carbon $fin): array
    {
        $estados = Cita::whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->get();

        $mapaLabels = [
            'pendiente'  => 'Pendiente',
            'confirmada' => 'Confirmada',
            'cancelada'  => 'Cancelada',
            'completada' => 'Completada',
            'no_asistio' => 'No asistió',
        ];

        $labels = [];
        $data   = [];

        foreach ($estados as $e) {
            $labels[] = $mapaLabels[$e->estado] ?? $e->estado;
            $data[]   = $e->total;
        }

        return compact('labels', 'data');
    }

    private function clientesNuevosVsRecurrentes(Carbon $inicio, Carbon $fin): array
    {
        // Agrupar por semanas o meses según el periodo
        $diffDays = $inicio->diffInDays($fin);

        if ($diffDays <= 14) {
            // Por día
            $format     = 'Y-m-d';
            $labelFmt   = 'D MMM';
            $step       = 'day';
        } elseif ($diffDays <= 90) {
            // Por semana
            $format     = 'Y-W';
            $labelFmt   = '[Sem] W';
            $step       = 'week';
        } else {
            // Por mes
            $format     = 'Y-m';
            $labelFmt   = 'MMM YYYY';
            $step       = 'month';
        }

        $periodos = [];
        $cursor   = $inicio->copy();

        while ($cursor->lte($fin)) {
            $key = $cursor->format($format);
            $periodos[$key] = [
                'label'       => $cursor->locale('es')->isoFormat($labelFmt),
                'nuevos'      => 0,
                'recurrentes' => 0,
            ];
            $cursor->add(1, $step);
        }

        // Contar clientes nuevos
        $clientesNuevos = Cliente::whereBetween('created_at', [$inicio, $fin])->get();
        foreach ($clientesNuevos as $cliente) {
            $key = $cliente->created_at->format($format);
            if (isset($periodos[$key])) {
                $periodos[$key]['nuevos']++;
            }
        }

        // Contar citas de clientes recurrentes (total_visitas > 1)
        $citasRecurrentes = Cita::whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->whereHas('cliente', fn($q) => $q->where('total_visitas', '>', 1))
            ->get();

        foreach ($citasRecurrentes as $cita) {
            $key = $cita->fecha->format($format);
            if (isset($periodos[$key])) {
                $periodos[$key]['recurrentes']++;
            }
        }

        $labels       = array_column(array_values($periodos), 'label');
        $nuevos       = array_column(array_values($periodos), 'nuevos');
        $recurrentes  = array_column(array_values($periodos), 'recurrentes');

        return compact('labels', 'nuevos', 'recurrentes');
    }

    private function horasPico(Carbon $inicio, Carbon $fin): array
    {
        $horas = Cita::whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->whereNotIn('estado', ['cancelada'])
            ->selectRaw("SUBSTRING(hora_inicio, 1, 2) as hora, COUNT(*) as total")
            ->groupBy('hora')
            ->orderBy('hora')
            ->get();

        $labels = [];
        $data   = [];

        foreach ($horas as $h) {
            $horaInt  = (int) $h->hora;
            $labels[] = Carbon::createFromTime($horaInt, 0)->format('g A');
            $data[]   = $h->total;
        }

        return compact('labels', 'data');
    }

    private function tendenciaCitas(Carbon $inicio, Carbon $fin, string $periodo): array
    {
        $diffDays = $inicio->diffInDays($fin);

        if ($diffDays <= 14) {
            $groupBy  = 'fecha';
            $labelFmt = 'D MMM';
        } elseif ($diffDays <= 90) {
            $groupBy  = 'fecha';
            $labelFmt = 'D MMM';
        } else {
            $groupBy  = 'fecha';
            $labelFmt = 'MMM YYYY';
        }

        $citas = Cita::whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->selectRaw("fecha, COUNT(*) as total")
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();

        $labels = [];
        $data   = [];

        if ($diffDays > 90) {
            // Agrupar por mes
            $agrupado = $citas->groupBy(fn($c) => Carbon::parse($c->fecha)->format('Y-m'));
            foreach ($agrupado as $key => $grupo) {
                $labels[] = Carbon::parse($key . '-01')->locale('es')->isoFormat('MMM YYYY');
                $data[]   = $grupo->sum('total');
            }
        } else {
            foreach ($citas as $c) {
                $labels[] = Carbon::parse($c->fecha)->locale('es')->isoFormat($labelFmt);
                $data[]   = $c->total;
            }
        }

        return compact('labels', 'data');
    }

    private function topClientes(Carbon $inicio, Carbon $fin): array
    {
        return Cita::whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->where('estado', 'completada')
            ->with('cliente')
            ->get()
            ->groupBy('cliente_id')
            ->map(function ($citas) {
                $cliente = $citas->first()->cliente;
                return [
                    'nombre'  => $cliente ? $cliente->nombre : 'Desconocido',
                    'visitas' => $citas->count(),
                    'gasto'   => round($citas->sum(fn($c) => $c->precio_cobrado ?? optional($c->servicio)->precio ?? 0), 2),
                ];
            })
            ->sortByDesc('visitas')
            ->take(10)
            ->values()
            ->toArray();
    }
}
