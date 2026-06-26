<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Servicio;
use App\Models\Barbero;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    // ─── Vista principal ──────────────────────────────────────────────────

    public function index()
    {
        $barberiaId = Auth::user()->barberia_id;
        $servicios = Servicio::where('barberia_id', $barberiaId)->where('activo', true)->get();
        $barberos = Barbero::where('barberia_id', $barberiaId)->where('activo', true)->get();

        $chatsPausados = Cliente::where('barberia_id', $barberiaId)
            ->whereHas('convEstado', function ($query) {
                $query->where('modo_asesor', true);
            })->with('convEstado')->get();

        return view('admin.dashboard', compact('servicios', 'barberos', 'chatsPausados'));
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
            'servicios_hoy'      => $this->serviciosHoy(),
            'ingresos_hoy'       => $this->ingresosHoyEspecialista(),
            'tendencia_citas'    => $this->tendenciaCitas($fechaInicio, $fechaFin, $periodo),
        ]);
    }

    // ─── Citas pendientes (vista lista) ─────────────────────────────────────

    public function citasPendientes(): JsonResponse
    {
        $citas = $this->getCitasBaseQuery()
            ->whereIn('estado', ['pendiente', 'confirmada'])
            ->whereDate('fecha', '>=', today())
            ->with(['cliente', 'servicios', 'barbero'])
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->get()
            ->map(fn($c) => [
                'id'       => $c->id,
                'cliente'  => $c->cliente->nombre ?? 'Sin nombre',
                'telefono' => $c->cliente->telefono ?? '',
                'servicios'=> $c->nombresServicios(),
                'fecha'    => Carbon::parse($c->fecha)->locale('es')->isoFormat('dddd D [de] MMMM'),
                'hora'     => Carbon::parse($c->hora_inicio)->format('g:i A'),
                'barbero'  => $c->barbero->nombre ?? 'Cualquiera',
                'estado'   => $c->estado,
            ]);

        return response()->json($citas);
    }

    // ─── Exportar PDF ─────────────────────────────────────────────────────

    public function registrarServicioLocal(Request $request)
    {
        $request->validate([
            'servicios'      => 'required|array',
            'barbero_id'     => 'nullable|exists:barberos,id',
            'precio_cobrado' => 'required|numeric|min:0',
            'telefono'       => 'nullable|string|max:20',
            'nombre'         => 'nullable|string|max:100',
        ]);

        $barberiaId = Auth::user()->barberia_id;

        // Determinar el cliente
        if ($request->telefono) {
            $cliente = Cliente::firstOrCreate(
                ['telefono' => $request->telefono],
                ['nombre' => $request->nombre ?? 'Cliente Local']
            );
        } else {
            $cliente = Cliente::firstOrCreate(
                ['telefono' => '0000000000'],
                ['nombre' => $request->nombre ?? 'Cliente Mostrador']
            );
        }

        $duracionTotal = (int) Servicio::whereIn('id', $request->servicios)->sum('duracion_min');
        $horaInicio = now()->format('H:i:s');
        $horaFin = now()->addMinutes($duracionTotal)->format('H:i:s');

        $cita = Cita::create([
            'barberia_id'    => $barberiaId,
            'cliente_id'     => $cliente->id,
            'barbero_id'     => $request->barbero_id,
            'fecha'          => now()->toDateString(),
            'hora_inicio'    => $horaInicio,
            'hora_fin'       => $horaFin,
            'estado'         => 'completada',
            'precio_cobrado' => $request->precio_cobrado,
            'notas'          => 'Servicio registrado localmente',
        ]);

        $cita->servicios()->attach($request->servicios);
        $cliente->increment('total_visitas');

        // Disparar mensaje de WhatsApp para pedir calificación SOLO si el teléfono no es el genérico
        if ($request->telefono && $request->telefono !== '0000000000') {
            try {
                $botService = app(\App\Services\BotService::class);
                $botService->pedirCalificacion($cita);
            } catch (\Exception $e) {
                // Ignore errors if WhatsApp fails so it doesn't break the UI
                \Illuminate\Support\Facades\Log::error("Error pidiendo calificación local: " . $e->getMessage());
            }
        }

        return response()->json(['success' => true]);
    }

    public function completarCita(Request $request, Cita $cita)
    {
        $barberiaId = Auth::user()->barberia_id;
        
        if ($cita->barberia_id !== $barberiaId) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $cita->update([
            'estado' => 'completada',
            // Si quieres capturar el precio cobrado exacto lo podrías pasar por el Request,
            // por ahora, asumiremos que se cobró lo que valen los servicios (o precio variable resuelto manual)
        ]);

        // Disparar mensaje de WhatsApp para pedir calificación
        $botService = app(\App\Services\BotService::class);
        $botService->pedirCalificacion($cita);

        return response()->json(['success' => true]);
    }

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

    // ─── Reactivar Bot Manualmente ──────────────────────────────────────────

    public function reactivarBot(Request $request, $telefono)
    {
        $barberiaId = Auth::user()->barberia_id;
        $cliente = Cliente::where('telefono', $telefono)->where('barberia_id', $barberiaId)->firstOrFail();
        $estado = \App\Models\ConvEstado::where('telefono', $telefono)->where('modo_asesor', true)->firstOrFail();

        $estado->desactivarModoAsesor();

        // Enviar mensaje al cliente
        try {
            $botService = app(\App\Services\WhatsAppApiService::class);
            $mensaje = "Un administrador ha reactivado el asistente automático. ¿En qué más te puedo ayudar?";
            $botService->enviarTexto($telefono, $cliente->barberia, $mensaje);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error reactivando bot manualmente: " . $e->getMessage());
        }

        return back()->with('success', "El bot ha sido reactivado para el cliente {$cliente->nombre}.");
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

    private function getCitasBaseQuery()
    {
        $query = Cita::where('barberia_id', Auth::user()->barberia_id);
        if (Auth::user()->rol === 'empleado') {
            $query->where('barbero_id', Auth::user()->barbero_id);
        }
        return $query;
    }

    private function calcularKpis(Carbon $inicio, Carbon $fin): array
    {
        $barberiaId = Auth::user()->barberia_id;
        $citas = $this->getCitasBaseQuery()
            ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()]);

        $totalCitas      = (clone $citas)->count();
        $completadas     = (clone $citas)->where('estado', 'completada')->count();
        $canceladas      = (clone $citas)->where('estado', 'cancelada')->count();
        $noAsistio       = (clone $citas)->where('estado', 'no_asistio')->count();

        // Ingresos: usar precio_cobrado si existe, sino la suma de los servicios de la cita
        $ingresos = $this->getCitasBaseQuery()
            ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->where('estado', 'completada')
            ->with('servicios')
            ->get()
            ->sum(function ($cita) {
                return $cita->precio_cobrado ?? $cita->servicios->sum('precio');
            });

        $clientesNuevos = Cliente::whereHas('citas', function($q) use ($barberiaId) {
            $q->where('barberia_id', $barberiaId);
        })->whereBetween('created_at', [$inicio, $fin])->count();

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
        $citas = $this->getCitasBaseQuery()
            ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->where('estado', 'completada')
            ->with('servicios')
            ->orderBy('fecha')
            ->get()
            ->groupBy(fn($cita) => $cita->fecha->format('Y-m-d'));

        $labels = [];
        $data   = [];

        foreach ($citas as $fecha => $grupo) {
            $labels[] = Carbon::parse($fecha)->locale('es')->isoFormat('D MMM');
            $data[]   = round($grupo->sum(fn($c) => $c->precio_cobrado ?? $c->servicios->sum('precio')), 2);
        }

        return compact('labels', 'data');
    }

    private function serviciosPopulares(Carbon $inicio, Carbon $fin): array
    {
        $barberiaId = Auth::user()->barberia_id;

        // Consultar desde la tabla pivote cita_servicio
        $rows = DB::table('cita_servicio')
            ->join('citas', 'cita_servicio.cita_id', '=', 'citas.id')
            ->join('servicios', 'cita_servicio.servicio_id', '=', 'servicios.id')
            ->where('citas.barberia_id', $barberiaId)
            ->whereBetween('citas.fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->whereNotIn('citas.estado', ['cancelada'])
            ->select('servicios.nombre', DB::raw('COUNT(*) as total'))
            ->groupBy('servicios.id', 'servicios.nombre')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return [
            'labels' => $rows->pluck('nombre')->toArray(),
            'data'   => $rows->pluck('total')->toArray(),
        ];
    }

    private function serviciosHoy(): array
    {
        $barberiaId = Auth::user()->barberia_id;
        $hoy = now()->toDateString();

        $rows = DB::table('cita_servicio')
            ->join('citas', 'cita_servicio.cita_id', '=', 'citas.id')
            ->join('servicios', 'cita_servicio.servicio_id', '=', 'servicios.id')
            ->where('citas.barberia_id', $barberiaId)
            ->where('citas.fecha', $hoy)
            ->whereNotIn('citas.estado', ['cancelada', 'no_asistio'])
            ->select('servicios.nombre', DB::raw('COUNT(*) as total'))
            ->groupBy('servicios.id', 'servicios.nombre')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $rows->pluck('nombre')->toArray(),
            'data'   => $rows->pluck('total')->toArray(),
            'total'  => $rows->sum('total')
        ];
    }

    private function ingresosHoyEspecialista(): array
    {
        $citas = $this->getCitasBaseQuery()
            ->whereDate('fecha', today())
            ->where('estado', 'completada')
            ->with(['barbero', 'servicios'])
            ->get();

        $ingresosPorBarbero = [];

        foreach ($citas as $cita) {
            $barbero = $cita->barbero->nombre ?? 'Generales';
            $ingreso = $cita->precio_cobrado ?? $cita->servicios->sum('precio');
            
            if (!isset($ingresosPorBarbero[$barbero])) {
                $ingresosPorBarbero[$barbero] = 0;
            }
            $ingresosPorBarbero[$barbero] += $ingreso;
        }

        return [
            'labels' => array_keys($ingresosPorBarbero),
            'data'   => array_values($ingresosPorBarbero),
            'total'  => array_sum($ingresosPorBarbero)
        ];
    }

    private function clientesNuevosVsRecurrentes(Carbon $inicio, Carbon $fin): array
    {
        $barberiaId = Auth::user()->barberia_id;
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
        $clientesNuevos = Cliente::whereHas('citas', function($q) use ($barberiaId) {
            $q->where('barberia_id', $barberiaId);
        })->whereBetween('created_at', [$inicio, $fin])->get();
        foreach ($clientesNuevos as $cliente) {
            $key = $cliente->created_at->format($format);
            if (isset($periodos[$key])) {
                $periodos[$key]['nuevos']++;
            }
        }

        // Contar citas de clientes recurrentes (total_visitas > 1)
        $citasRecurrentes = Cita::where('barberia_id', $barberiaId)
            ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
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
        $barberiaId = Auth::user()->barberia_id;
        $horas = Cita::where('barberia_id', $barberiaId)
            ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
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
        $barberiaId = Auth::user()->barberia_id;
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

        $citas = Cita::where('barberia_id', $barberiaId)
            ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
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
        $barberiaId = Auth::user()->barberia_id;
        return Cita::where('barberia_id', $barberiaId)
            ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->where('estado', 'completada')
            ->with(['cliente', 'servicios'])
            ->get()
            ->groupBy('cliente_id')
            ->map(function ($citas) {
                $cliente = $citas->first()->cliente;
                return [
                    'nombre'  => $cliente ? $cliente->nombre : 'Desconocido',
                    'visitas' => $citas->count(),
                    'gasto'   => round($citas->sum(fn($c) => $c->precio_cobrado ?? $c->servicios->sum('precio')), 2),
                ];
            })
            ->sortByDesc('visitas')
            ->take(10)
            ->values()
            ->toArray();
    }
}
