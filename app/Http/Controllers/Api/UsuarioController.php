<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UsuarioController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => Usuario::with('rol')->orderBy('nombre_completo')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre_completo' => 'required|string|max:100',
            'email'           => 'required|email|unique:usuarios',
            'password'        => 'required|string|min:8',
            'rol_id'          => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $usuario = Usuario::create([
            'nombre_completo' => $request->nombre_completo,
            'email'           => $request->email,
            'password_hash'   => Hash::make($request->password),
            'rol_id'          => $request->rol_id,
        ]);

        return response()->json([
            'success' => true,
            'data'    => $usuario->load('rol'),
        ], 201);
    }

    public function show(Usuario $usuario): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $usuario->load('rol'),
        ]);
    }

    public function update(Request $request, Usuario $usuario): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre_completo' => 'string|max:100',
            'email'           => 'email|unique:usuarios,email,' . $usuario->id,
            'rol_id'          => 'exists:roles,id',
            'activo'          => 'boolean',
            'password'        => 'nullable|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $datos = $request->only(['nombre_completo', 'email', 'rol_id', 'activo']);

        if ($request->filled('password')) {
            $datos['password_hash'] = Hash::make($request->password);
        }

        $usuario->update($datos);

        return response()->json(['success' => true, 'data' => $usuario->load('rol')]);
    }

    public function destroy(Usuario $usuario): JsonResponse
    {
        $usuario->update(['activo' => false]);

        return response()->json(['success' => true, 'message' => 'Usuario desactivado.']);
    }
}
