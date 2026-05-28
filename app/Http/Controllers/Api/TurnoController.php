<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Turno;
use App\Services\TurnoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class TurnoController extends Controller
{
    public function __construct(private TurnoService $turnoService) {}

    public function index(Request $request): JsonResponse
    {
        $query = Turno::with(['usuarioApertura', 'usuarioCierre']);

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->has('fecha')) {
            $query->whereDate('fecha_apertura', $request->fecha);
        }

        return response()->json([
            'success' => true,
            'data'    => $query->orderBy('fecha_apertura', 'desc')->get(),
        ]);
    }

    public function abrir(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'caja_inicial' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $usuario = JWTAuth::parseToken()->authenticate();

        try {
            $turno = $this->turnoService->abrirTurno($usuario->id, $request->caja_inicial);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 409);
        }

        return response()->json(['success' => true, 'data' => $turno->load('usuarioApertura')], 201);
    }

    public function cerrar(Request $request, Turno $turno): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'total_efectivo_contado' => 'required|numeric|min:0',
            'total_real'             => 'required|numeric|min:0',
            'total_tarjeta'          => 'numeric|min:0',
            'total_transferencia'    => 'numeric|min:0',
            'billetes_200'           => 'integer|min:0',
            'billetes_100'           => 'integer|min:0',
            'billetes_50'            => 'integer|min:0',
            'billetes_20'            => 'integer|min:0',
            'billetes_10'            => 'integer|min:0',
            'monedas_total'          => 'numeric|min:0',
            'observaciones'          => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $usuario = JWTAuth::parseToken()->authenticate();

        try {
            $corte = $this->turnoService->cerrarTurno($turno, $request->all(), $usuario->id);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 409);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'turno' => $turno->fresh()->load(['usuarioApertura', 'usuarioCierre']),
                'corte' => $corte,
            ],
        ]);
    }

    public function miTurnoActivo(): JsonResponse
    {
        $usuario = JWTAuth::parseToken()->authenticate();

        $turno = Turno::where('usuario_apertura', $usuario->id)
            ->where('estado', 'abierto')
            ->first();

        return response()->json([
            'success' => true,
            'data'    => $turno,
        ]);
    }

    public function show(Turno $turno): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $turno->load(['usuarioApertura', 'usuarioCierre', 'corteCaja', 'ventas']),
        ]);
    }
}
