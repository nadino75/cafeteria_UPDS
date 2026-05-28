<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GastoOperativo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class GastoOperativoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = GastoOperativo::with(['turno', 'usuario']);

        if ($request->has('turno_id')) {
            $query->where('turno_id', $request->turno_id);
        }
        if ($request->has('categoria')) {
            $query->where('categoria', $request->categoria);
        }
        if ($request->has('fecha')) {
            $query->whereDate('fecha', $request->fecha);
        }

        return response()->json([
            'success' => true,
            'data'    => $query->orderBy('fecha', 'desc')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'turno_id'       => 'nullable|exists:turnos,id',
            'categoria'      => 'required|in:servicios,mantenimiento,insumos,nomina,impuestos,otros',
            'descripcion'    => 'required|string',
            'monto'          => 'required|numeric|min:0.01',
            'comprobante_url'=> 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $usuario = JWTAuth::parseToken()->authenticate();

        $gasto = GastoOperativo::create(array_merge(
            $request->only(['turno_id', 'categoria', 'descripcion', 'monto', 'comprobante_url']),
            ['usuario_id' => $usuario->id]
        ));

        return response()->json(['success' => true, 'data' => $gasto->load('turno')], 201);
    }

    public function show(GastoOperativo $gastoOperativo): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $gastoOperativo->load(['turno', 'usuario']),
        ]);
    }

    public function destroy(GastoOperativo $gastoOperativo): JsonResponse
    {
        $gastoOperativo->delete();

        return response()->json(['success' => true, 'message' => 'Gasto eliminado.']);
    }
}
