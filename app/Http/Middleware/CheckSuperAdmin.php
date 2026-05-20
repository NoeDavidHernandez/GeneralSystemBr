<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() || !Auth::user()->is_superadmin) {
            // Si no es superadmin y está intentando acceder a la zona de superadmin,
            // lo mandamos a su dashboard normal o de vuelta.
            return redirect()->route('admin.dashboard')->with('error', 'Acceso denegado. No eres Super Administrador.');
        }

        return $next($request);
    }
}
