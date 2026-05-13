<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminDashboardController;

Route::get('/', function () {
    return view('welcome');
});

// ─── Panel de Administración ──────────────────────────────────────────
Route::prefix('admin')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/datos', [AdminDashboardController::class, 'datos'])->name('admin.datos');
    Route::get('/reporte-pdf', [AdminDashboardController::class, 'exportarPdf'])->name('admin.reporte.pdf');
});
