<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AuthController;

use App\Http\Controllers\SuperAdminController;

Route::get('/', function () {
    // Si está autenticado y es superadmin, mandarlo al superadmin
    if (auth()->check() && auth()->user()->is_superadmin) {
        return redirect()->route('superadmin.dashboard');
    }
    return redirect()->route('admin.dashboard');
});

// ─── Autenticación ────────────────────────────────────────────────────
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ─── Rutas de Cambio de Contraseña Obligatorio ────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/password/setup', [\App\Http\Controllers\PasswordController::class, 'showSetPasswordForm'])->name('password.setup');
    Route::post('/password/setup', [\App\Http\Controllers\PasswordController::class, 'updatePassword']);
});

// ─── Panel de Administración (Clientes) ───────────────────────────────
Route::prefix('admin')->middleware(['auth', \App\Http\Middleware\CheckBarberiaActiva::class, 'force.password'])->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/datos', [AdminDashboardController::class, 'datos'])->name('admin.datos');
    Route::get('/citas-pendientes', [AdminDashboardController::class, 'citasPendientes'])->name('admin.citas.pendientes');
    Route::post('/citas/{cita}/completar', [AdminDashboardController::class, 'completarCita'])->name('admin.citas.completar');
    Route::post('/citas/local', [AdminDashboardController::class, 'registrarServicioLocal'])->name('admin.citas.local');
    Route::get('/reporte-pdf', [AdminDashboardController::class, 'exportarPdf'])->name('admin.reporte.pdf');
    
    // Clientes
    Route::get('/clientes', [\App\Http\Controllers\ClientesController::class, 'index'])->name('admin.clientes.index');
    Route::post('/clientes', [\App\Http\Controllers\ClientesController::class, 'store'])->name('admin.clientes.store');
    Route::put('/clientes/{cliente}', [\App\Http\Controllers\ClientesController::class, 'update'])->name('admin.clientes.update');
    
    // Agenda
    Route::get('/agenda', [\App\Http\Controllers\AgendaController::class, 'index'])->name('admin.agenda');
    Route::get('/agenda/eventos', [\App\Http\Controllers\AgendaController::class, 'eventos'])->name('admin.agenda.eventos');
    Route::post('/agenda/guardar', [\App\Http\Controllers\AgendaController::class, 'guardarCita'])->name('admin.agenda.guardar');
    // Empleados
    Route::get('/empleados', [\App\Http\Controllers\EmpleadosController::class, 'index'])->name('admin.empleados.index');
    Route::post('/empleados', [\App\Http\Controllers\EmpleadosController::class, 'store'])->name('admin.empleados.store');
    Route::put('/empleados/{empleado}', [\App\Http\Controllers\EmpleadosController::class, 'update'])->name('admin.empleados.update');
    Route::delete('/empleados/{empleado}', [\App\Http\Controllers\EmpleadosController::class, 'destroy'])->name('admin.empleados.destroy');
    
    // Configuracion
    Route::get('/configuracion', [\App\Http\Controllers\ConfiguracionController::class, 'index'])->name('admin.configuracion.index');
    Route::put('/configuracion', [\App\Http\Controllers\ConfiguracionController::class, 'update'])->name('admin.configuracion.update');

    // Servicios
    Route::get('/servicios', [\App\Http\Controllers\ServiciosController::class, 'index'])->name('admin.servicios.index');
    Route::post('/servicios', [\App\Http\Controllers\ServiciosController::class, 'store'])->name('admin.servicios.store');
    Route::put('/servicios/{servicio}', [\App\Http\Controllers\ServiciosController::class, 'update'])->name('admin.servicios.update');
    Route::delete('/servicios/{servicio}', [\App\Http\Controllers\ServiciosController::class, 'destroy'])->name('admin.servicios.destroy');
});

// ─── Panel de Super Administrador (SaaS) ──────────────────────────────
Route::prefix('superadmin')->middleware(['auth', \App\Http\Middleware\CheckSuperAdmin::class])->group(function () {
    Route::get('/', [SuperAdminController::class, 'index'])->name('superadmin.dashboard');
    Route::get('/datos', [SuperAdminController::class, 'datos'])->name('superadmin.datos');
    Route::post('/barberias/{id}/toggle', [SuperAdminController::class, 'toggleStatus'])->name('superadmin.barberias.toggle');

    // Negocios (CRUD avanzado SaaS)
    Route::get('/negocios/{barberia}', [\App\Http\Controllers\SuperAdminNegociosController::class, 'show'])->name('superadmin.negocios.show');
    Route::put('/negocios/{barberia}', [\App\Http\Controllers\SuperAdminNegociosController::class, 'update'])->name('superadmin.negocios.update');
    Route::post('/negocios/{barberia}/pagos', [\App\Http\Controllers\SuperAdminNegociosController::class, 'storePago'])->name('superadmin.negocios.pagos.store');

    // Equipo NLogic
    Route::get('/team', [\App\Http\Controllers\NlogicTeamController::class, 'index'])->name('superadmin.team.index');
    Route::post('/team', [\App\Http\Controllers\NlogicTeamController::class, 'store'])->name('superadmin.team.store');
    Route::delete('/team/{user}', [\App\Http\Controllers\NlogicTeamController::class, 'destroy'])->name('superadmin.team.destroy');
});
