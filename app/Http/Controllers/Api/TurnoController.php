<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CorteCaja;
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
        $usuario = JWTAuth::parseToken()->authenticate();
        $esAdmin = $usuario->rol?->es_superadmin === true;
        $esGerente = $usuario->rol?->nombre === 'Gerente';

        $query = Turno::with(['usuarioApertura', 'usuarioCierre']);

        if (!$esAdmin && !$esGerente) {
            $query->where('usuario_apertura', $usuario->id);
        }

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
        $usuario = JWTAuth::parseToken()->authenticate();
        $esAdmin = $usuario->rol?->es_superadmin === true;

        if (!$esAdmin && $turno->usuario_apertura !== $usuario->id) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes cerrar un turno que no te pertenece.',
            ], 403);
        }

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

    public function pendientesValidar(): JsonResponse
    {
        $usuario = JWTAuth::parseToken()->authenticate();
        $esAdmin = $usuario->rol?->es_superadmin === true;
        $esGerente = $usuario->rol?->nombre === 'Gerente';

        if (!$esAdmin && !$esGerente) {
            return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
        }

        $cortes = CorteCaja::where('estado', 'pendiente')
            ->with(['turno.usuarioApertura', 'usuario'])
            ->orderBy('fecha_corte', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $cortes]);
    }

    public function resumenCierre(Turno $turno): JsonResponse
    {
        $usuario = JWTAuth::parseToken()->authenticate();
        $esAdmin = $usuario->rol?->es_superadmin === true;
        $esGerente = $usuario->rol?->nombre === 'Gerente';

        if (!$esAdmin && !$esGerente && $turno->usuario_apertura !== $usuario->id) {
            return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
        }

        $ventas = $turno->ventas()->where('estado', 'completada')->get();

        $efectivo     = $ventas->sum('pago_efectivo');
        $tarjeta      = $ventas->sum('pago_tarjeta');
        $transferencia = $ventas->sum('pago_transferencia');
        $qr           = $ventas->sum('pago_qr');
        $totalVentas  = $ventas->sum('total');
        $totalGastos  = $turno->gastos()->sum('monto');
        $cajaFinal    = $turno->caja_inicial + $totalVentas - $totalGastos;

        return response()->json([
            'success' => true,
            'data'    => [
                'efectivo_esperado'     => round($efectivo, 2),
                'tarjeta_esperado'      => round($tarjeta, 2),
                'transferencia_esperado'=> round($transferencia, 2),
                'qr_esperado'           => round($qr, 2),
                'total_ventas'          => round($totalVentas, 2),
                'total_gastos'          => round($totalGastos, 2),
                'caja_final_esperada'   => round($cajaFinal, 2),
            ],
        ]);
    }

    public function validarEntrega(Request $request, CorteCaja $corte): JsonResponse
    {
        $usuario = JWTAuth::parseToken()->authenticate();
        $esAdmin = $usuario->rol?->es_superadmin === true;
        $esGerente = $usuario->rol?->nombre === 'Gerente';

        if (!$esAdmin && !$esGerente) {
            return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
        }

        if ($corte->estado === 'entregado') {
            return response()->json(['success' => false, 'message' => 'Este corte ya fue validado como entregado.'], 409);
        }

        $corte->update([
            'estado'           => 'entregado',
            'validado_por'     => $usuario->id,
            'fecha_validacion' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $corte->fresh()->load(['turno', 'usuario', 'validador']),
        ]);
    }

    public function show(Turno $turno): JsonResponse
    {
        $usuario = JWTAuth::parseToken()->authenticate();
        $esAdmin = $usuario->rol?->es_superadmin === true;
        $esGerente = $usuario->rol?->nombre === 'Gerente';

        if (!$esAdmin && !$esGerente && $turno->usuario_apertura !== $usuario->id) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data'    => $turno->load(['usuarioApertura', 'usuarioCierre', 'corteCaja', 'ventas']),
        ]);
    }
}
