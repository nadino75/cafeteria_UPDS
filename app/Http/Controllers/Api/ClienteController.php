<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClienteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Cliente::query();

        if ($request->has('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('nombre', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('telefono', 'like', "%{$s}%");
            });
        }

        return response()->json([
            'success' => true,
            'data'    => $query->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre'   => 'required|string|max:100',
            'email'    => 'nullable|email|unique:clientes',
            'telefono' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $cliente = Cliente::create($request->only(['nombre', 'email', 'telefono']));

        return response()->json(['success' => true, 'data' => $cliente], 201);
    }

    public function show(Cliente $cliente): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $cliente,
        ]);
    }

    public function update(Request $request, Cliente $cliente): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre'   => 'string|max:100',
            'email'    => 'nullable|email|unique:clientes,email,' . $cliente->id,
            'telefono' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $cliente->update($request->only(['nombre', 'email', 'telefono']));

        return response()->json(['success' => true, 'data' => $cliente]);
    }

    public function canjearPuntos(Request $request, Cliente $cliente): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'puntos' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $puntos = (int) $request->puntos;

        if ($cliente->puntos_acumulados < $puntos) {
            return response()->json([
                'success' => false,
                'message' => 'Puntos insuficientes.',
            ], 409);
        }

        $cliente->increment('puntos_canjeados', $puntos);
        $cliente->decrement('puntos_acumulados', $puntos);

        return response()->json(['success' => true, 'data' => $cliente->fresh()]);
    }
}
