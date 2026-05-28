<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Models\LogActividad;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $usuario = Usuario::where('email', $request->email)->first();

        if (!$usuario || !Hash::check($request->password, $usuario->password_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        if (!$usuario->activo) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario inactivo. Contacte al administrador.',
            ], 403);
        }

        try {
            $token = JWTAuth::fromUser($usuario);
        } catch (JWTException) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo generar el token.',
            ], 500);
        }

        $usuario->update(['ultimo_login' => now()]);

        LogActividad::create([
            'usuario_id'  => $usuario->id,
            'accion'      => 'LOGIN',
            'modulo'      => 'auth',
            'descripcion' => 'Inicio de sesión exitoso',
            'ip_address'  => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'token'   => $token,
            'usuario' => [
                'id'              => $usuario->id,
                'nombre_completo' => $usuario->nombre_completo,
                'email'           => $usuario->email,
                'rol'             => $usuario->rol?->nombre,
                'es_superadmin'   => $usuario->rol?->es_superadmin,
            ],
        ]);
    }

    public function me(): JsonResponse
    {
        $usuario = JWTAuth::parseToken()->authenticate();
        $usuario->load('rol');

        return response()->json([
            'success' => true,
            'usuario' => [
                'id'              => $usuario->id,
                'nombre_completo' => $usuario->nombre_completo,
                'email'           => $usuario->email,
                'rol_id'          => $usuario->rol_id,
                'rol'             => $usuario->rol?->nombre,
                'es_superadmin'   => $usuario->rol?->es_superadmin,
                'activo'          => $usuario->activo,
                'ultimo_login'    => $usuario->ultimo_login,
            ],
        ]);
    }

    public function refresh(): JsonResponse
    {
        try {
            $token = JWTAuth::parseToken()->refresh();
        } catch (JWTException) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo refrescar el token.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'token'   => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $usuario = JWTAuth::parseToken()->authenticate();
            JWTAuth::parseToken()->invalidate();

            if ($usuario) {
                LogActividad::create([
                    'usuario_id'  => $usuario->id,
                    'accion'      => 'LOGOUT',
                    'modulo'      => 'auth',
                    'descripcion' => 'Cierre de sesión',
                    'ip_address'  => $request->ip(),
                ]);
            }
        } catch (JWTException) {
            // Token ya inválido
        }

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }
}
