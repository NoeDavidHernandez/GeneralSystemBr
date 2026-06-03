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

// ─── Panel de Administración (Clientes) ───────────────────────────────
Route::prefix('admin')->middleware(['auth', \App\Http\Middleware\CheckBarberiaActiva::class])->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/datos', [AdminDashboardController::class, 'datos'])->name('admin.datos');
    Route::get('/citas-pendientes', [AdminDashboardController::class, 'citasPendientes'])->name('admin.citas.pendientes');
    Route::post('/citas/{cita}/completar', [AdminDashboardController::class, 'completarCita'])->name('admin.citas.completar');
    Route::post('/citas/local', [AdminDashboardController::class, 'registrarServicioLocal'])->name('admin.citas.local');
    Route::get('/reporte-pdf', [AdminDashboardController::class, 'exportarPdf'])->name('admin.reporte.pdf');
});

// ─── Panel de Super Administrador (SaaS) ──────────────────────────────
Route::prefix('superadmin')->middleware(['auth', \App\Http\Middleware\CheckSuperAdmin::class])->group(function () {
    Route::get('/', [SuperAdminController::class, 'index'])->name('superadmin.dashboard');
    Route::get('/datos', [SuperAdminController::class, 'datos'])->name('superadmin.datos');
    Route::post('/barberias/{id}/toggle', [SuperAdminController::class, 'toggleStatus'])->name('superadmin.barberias.toggle');
});
