<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;

class PermisosMiddleware
{
    /**
     * Uso: permisos:modulo,accion
     * Ejemplo: permisos:ventas,crear
     */
    public function handle(Request $request, Closure $next, string $modulo, string $accion): Response
    {
        $usuario = JWTAuth::parseToken()->authenticate();

        // Superadmin bypasea todos los permisos
        if ($usuario->rol && $usuario->rol->es_superadmin) {
            return $next($request);
        }

        $tienePermiso = DB::table('rol_permisos as rp')
            ->join('modulos_sistema as m', 'rp.modulo_id', '=', 'm.id')
            ->join('acciones_sistema as a', 'rp.accion_id', '=', 'a.id')
            ->where('rp.rol_id', $usuario->rol_id)
            ->where('m.clave', $modulo)
            ->where('a.clave', $accion)
            ->exists();

        if (!$tienePermiso) {
            return response()->json([
                'success' => false,
                'message' => "Sin permiso para '{$accion}' en módulo '{$modulo}'.",
            ], 403);
        }

        return $next($request);
    }
}
