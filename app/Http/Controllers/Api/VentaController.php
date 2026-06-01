<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\DetalleVentaLote;
use App\Models\LoteInventario;
use App\Models\MovimientoInventario;
use App\Models\Venta;
use App\Services\ContabilidadService;
use App\Services\VentaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class VentaController extends Controller
{
    public function __construct(
        private VentaService $ventaService,
        private ?ContabilidadService $contabilidad = null
    ) {
        $this->contabilidad ??= app(ContabilidadService::class);
    }

    public function index(Request $request): JsonResponse
    {
        $usuario = JWTAuth::parseToken()->authenticate();
        $esAdmin = $usuario->rol?->es_superadmin === true;
        $esGerente = $usuario->rol?->nombre === 'Gerente';

        $query = Venta::with(['usuario', 'cliente', 'detalles']);

        if (!$esAdmin && !$esGerente) {
            $query->where('usuario_id', $usuario->id);
        }

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
            'metodo_pago'            => 'required|in:efectivo,tarjeta,transferencia,qr,mixto',
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
            'pago_efectivo'         => 'nullable|numeric|min:0',
            'pago_tarjeta'          => 'nullable|numeric|min:0',
            'pago_transferencia'    => 'nullable|numeric|min:0',
            'pago_qr'               => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $usuario = JWTAuth::parseToken()->authenticate();

        try {
            $venta = $this->ventaService->registrarVenta(
                turnoId:          $request->turno_id,
                usuarioId:        $usuario->id,
                items:            $request->items,
                metodoPago:       $request->metodo_pago,
                clienteId:        $request->cliente_id,
                descuentoGlobal:  $request->descuento ?? 0,
                impuesto:         $request->impuesto  ?? 0,
                nota:             $request->nota,
                pagoEfectivo:     $request->pago_efectivo,
                pagoTarjeta:      $request->pago_tarjeta,
                pagoTransferencia: $request->pago_transferencia,
                pagoQr:           $request->pago_qr,
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

        $usuario = JWTAuth::parseToken()->authenticate();

        DB::transaction(function () use ($venta, $usuario) {
            $venta->load(['detalles.lotes.lote.producto']);

            foreach ($venta->detalles as $detalle) {
                foreach ($detalle->lotes as $detalleLote) {
                    $lote = $detalleLote->lote;

                    // Restaurar cantidad disponible del lote
                    $lote->cantidad_disponible += $detalleLote->cantidad_consumida;
                    $lote->estado = 'disponible';
                    $lote->save();

                    // Restaurar stock del producto
                    $lote->producto->increment('stock_actual', $detalleLote->cantidad_consumida);

                    // Crear movimiento de reversión
                    MovimientoInventario::create([
                        'producto_id'     => $lote->producto_id,
                        'lote_id'         => $lote->id,
                        'tipo'            => 'entrada',
                        'cantidad'        => $detalleLote->cantidad_consumida,
                        'costo_unitario'  => $detalleLote->costo_unitario_lote,
                        'motivo'          => 'Cancelación de venta',
                        'usuario_id'      => $usuario->id,
                        'referencia_tipo' => 'venta',
                        'referencia_id'   => $venta->id,
                    ]);
                }
            }

            // Revertir puntos del cliente
            if ($venta->cliente_id) {
                Cliente::find($venta->cliente_id)?->decrement('puntos_acumulados', (int) $venta->total);
            }

            $venta->update(['estado' => 'cancelada']);

            // Revertir asiento contable
            try {
                $this->contabilidad->generarAsientoVenta($venta);
            } catch (\Throwable $e) {
                report($e);
            }
        });

        return response()->json(['success' => true, 'message' => 'Venta cancelada y stock restaurado.']);
    }
}
