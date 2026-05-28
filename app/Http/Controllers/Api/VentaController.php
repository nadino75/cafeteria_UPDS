<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use App\Services\VentaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class VentaController extends Controller
{
    public function __construct(private VentaService $ventaService) {}

    public function index(Request $request): JsonResponse
    {
        $query = Venta::with(['usuario', 'cliente', 'detalles']);

        if ($request->has('turno_id')) {
            $query->where('turno_id', $request->turno_id);
        }
        if ($request->has('fecha')) {
            $query->whereDate('fecha', $request->fecha);
        }
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        return response()->json([
            'success' => true,
            'data'    => $query->orderBy('fecha', 'desc')->paginate(50),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'turno_id'               => 'required|exists:turnos,id',
            'metodo_pago'            => 'required|in:efectivo,tarjeta,transferencia,mixto',
            'cliente_id'             => 'nullable|exists:clientes,id',
            'descuento'              => 'numeric|min:0',
            'impuesto'               => 'numeric|min:0',
            'nota'                   => 'nullable|string',
            'items'                  => 'required|array|min:1',
            'items.*.tipo'           => 'required|in:producto,menu',
            'items.*.id'             => 'required|integer',
            'items.*.cantidad'       => 'required|integer|min:1',
            'items.*.precio_unitario'=> 'required|numeric|min:0',
            'items.*.descuento'      => 'numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $usuario = JWTAuth::parseToken()->authenticate();

        try {
            $venta = $this->ventaService->registrarVenta(
                turnoId:         $request->turno_id,
                usuarioId:       $usuario->id,
                items:           $request->items,
                metodoPago:      $request->metodo_pago,
                clienteId:       $request->cliente_id,
                descuentoGlobal: $request->descuento ?? 0,
                impuesto:        $request->impuesto  ?? 0,
                nota:            $request->nota,
            );
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 409);
        }

        return response()->json(['success' => true, 'data' => $venta], 201);
    }

    public function show(Venta $venta): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $venta->load(['usuario', 'cliente', 'turno', 'detalles.lotes']),
        ]);
    }

    public function cancelar(Request $request, Venta $venta): JsonResponse
    {
        if ($venta->estado !== 'completada') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden cancelar ventas completadas.',
            ], 409);
        }

        $venta->update(['estado' => 'cancelada']);

        return response()->json(['success' => true, 'message' => 'Venta cancelada.']);
    }
}
