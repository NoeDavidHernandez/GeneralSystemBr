<?php

use App\Http\Controllers\WhatsAppController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas del webhook de WhatsApp (Meta Cloud API)
|--------------------------------------------------------------------------
|
| Meta necesita dos rutas sobre el mismo endpoint:
|   GET  → verificación inicial (solo se llama una vez al configurar)
|   POST → mensajes entrantes (se llama en cada mensaje)
|
| URL que registras en Meta Business:
|   https://tudominio.com/api/whatsapp/webhook
|
|
*/

Route::prefix('whatsapp')->group(function () {

    // Meta llama esto una sola vez para verificar que el servidor es tuyo
    Route::get('/webhook', [WhatsAppController::class, 'verificar'])
        ->name('whatsapp.verificar');

    // Meta llama esto en cada mensaje entrante
    Route::post('/webhook', [WhatsAppController::class, 'webhook'])
        ->name('whatsapp.webhook');

});

// ─── Ruta temporal para ejecutar migraciones sin middleware de sesión ───
Route::get('/ejecutar-migraciones-secret-nlogic', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--force' => true]);
        return "¡Migraciones ejecutadas con éxito desde API!<br><pre>" . \Illuminate\Support\Facades\Artisan::output() . "</pre>";
    } catch (\Exception $e) {
        return "Error ejecutando migraciones: " . $e->getMessage();
    }
});

// ─── Ruta temporal para insertar registros de prueba (Superadmin y Barberías) ───
Route::get('/ejecutar-seeders-secret-nlogic', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
        return "¡Registros de prueba creados con éxito!<br><pre>" . \Illuminate\Support\Facades\Artisan::output() . "</pre>";
    } catch (\Exception $e) {
        return "Error insertando registros: " . $e->getMessage();
    }
});