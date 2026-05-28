<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $usuario = JWTAuth::parseToken()->authenticate();

            if (!$usuario || !$usuario->activo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autorizado o inactivo.',
                ], 401);
            }
        } catch (TokenExpiredException) {
            return response()->json([
                'success' => false,
                'message' => 'Token expirado.',
                'code'    => 'token_expired',
            ], 401);
        } catch (TokenInvalidException) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido.',
                'code'    => 'token_invalid',
            ], 401);
        } catch (JWTException) {
            return response()->json([
                'success' => false,
                'message' => 'Token no proporcionado.',
                'code'    => 'token_absent',
            ], 401);
        }

        return $next($request);
    }
}
