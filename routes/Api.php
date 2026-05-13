<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WhatsAppController;

// ─── WhatsApp Webhook (Meta Cloud API) ────────────────────────────────
Route::get('whatsapp/webhook',  [WhatsAppController::class, 'verificar']);
Route::post('whatsapp/webhook', [WhatsAppController::class, 'webhook']);
