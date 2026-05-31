<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AsientoContable;
use App\Models\CierreDiario;
use App\Services\ContabilidadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class ContabilidadController extends Controller
{
    public function __construct(private ContabilidadService $contabilidad) {}

    public function tendencia(Request $request): JsonResponse
    {
        $meses = min((int) ($request->get('meses', 6)), 24);
        $anio  = $request->has('anio') ? (int) $request->anio : null;
        $mes   = $request->has('mes')  ? (int) $request->mes  : null;
        $data  = $this->contabilidad->obtenerTendenciaMensual($meses, $anio, $mes);

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function comparativa(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->contabilidad->obtenerComparativa(),
        ]);
    }

    public function pyg(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'desde' => 'nullable|date',
            'hasta' => 'nullable|date|after_or_equal:desde',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        return response()->json([
            'success' => true,
            'data'    => $this->contabilidad->obtenerPyG(
                $request->get('desde'),
                $request->get('hasta')
            ),
        ]);
    }

    public function balanceGeneral(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->contabilidad->obtenerBalanceGeneral(),
        ]);
    }

    public function asientos(Request $request): JsonResponse
    {
        $query = AsientoContable::with(['lineas.cuenta', 'usuario']);

        if ($request->has('fecha')) {
            $query->whereDate('fecha', $request->fecha);
        }
        if ($request->has('desde')) {
            $query->whereDate('fecha', '>=', $request->desde);
        }
        if ($request->has('hasta')) {
            $query->whereDate('fecha', '<=', $request->hasta);
        }
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        return response()->json([
            'success' => true,
            'data'    => $query->orderBy('fecha', 'desc')->orderBy('numero_asiento', 'desc')->get(),
        ]);
    }

    public function ejecutarCierre(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'fecha' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $fecha   = $request->get('fecha', today()->toDateString());
        $usuario = JWTAuth::parseToken()->authenticate();
        $cierre  = CierreDiario::where('fecha', $fecha)->first();

        if (!$cierre) {
            return response()->json(['success' => false, 'message' => 'No hay cierre diario para esta fecha.'], 404);
        }

        $balance = $this->contabilidad->ejecutarCierreDiario($cierre);

        return response()->json(['success' => true, 'data' => $balance->fresh()]);
    }

    public function cierresPendientes(): JsonResponse
    {
        $pendientes = CierreDiario::where('estado', '!=', 'cerrado')
            ->whereDate('fecha', today())
            ->with('usuario')
            ->orderBy('fecha', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $pendientes]);
    }
}
