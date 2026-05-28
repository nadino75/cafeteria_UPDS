<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\Producto;
use App\Services\FifoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class CompraController extends Controller
{
    public function __construct(private FifoService $fifo) {}

    public function index(Request $request): JsonResponse
    {
        $query = Compra::with(['proveedor', 'usuario']);

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->has('proveedor_id')) {
            $query->where('proveedor_id', $request->proveedor_id);
        }

        return response()->json([
            'success' => true,
            'data'    => $query->orderBy('fecha_orden', 'desc')->paginate(30),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'proveedor_id'             => 'required|exists:proveedores,id',
            'nota'                     => 'nullable|string',
            'items'                    => 'required|array|min:1',
            'items.*.producto_id'      => 'required|exists:productos,id',
            'items.*.cantidad_ordenada'=> 'required|integer|min:1',
            'items.*.costo_unitario'   => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $usuario = JWTAuth::parseToken()->authenticate();

        $compra = DB::transaction(function () use ($request, $usuario) {
            $subtotal = 0;
            $codigo   = $this->generarCodigo();

            $compra = Compra::create([
                'codigo'       => $codigo,
                'proveedor_id' => $request->proveedor_id,
                'usuario_id'   => $usuario->id,
                'nota'         => $request->nota,
            ]);

            foreach ($request->items as $item) {
                $sub = $item['cantidad_ordenada'] * $item['costo_unitario'];
                $subtotal += $sub;

                DetalleCompra::create([
                    'compra_id'          => $compra->id,
                    'producto_id'        => $item['producto_id'],
                    'cantidad_ordenada'  => $item['cantidad_ordenada'],
                    'costo_unitario'     => $item['costo_unitario'],
                    'subtotal'           => $sub,
                ]);
            }

            $compra->update(['subtotal' => $subtotal, 'total' => $subtotal]);

            return $compra;
        });

        return response()->json([
            'success' => true,
            'data'    => $compra->load(['proveedor', 'detalles.producto']),
        ], 201);
    }

    public function show(Compra $compra): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $compra->load(['proveedor', 'usuario', 'detalles.producto', 'detalles.loteGenerado']),
        ]);
    }

    /** Recibe la compra: crea lotes FIFO para cada ítem recibido */
    public function recibirCompra(Request $request, Compra $compra): JsonResponse
    {
        if ($compra->estado === 'cancelada') {
            return response()->json(['success' => false, 'message' => 'Compra cancelada.'], 409);
        }

        $validator = Validator::make($request->all(), [
            'items'                          => 'required|array|min:1',
            'items.*.detalle_compra_id'      => 'required|exists:detalle_compra,id',
            'items.*.cantidad_recibida'      => 'required|integer|min:1',
            'items.*.numero_lote'            => 'nullable|string',
            'items.*.fecha_vencimiento'      => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $usuario = JWTAuth::parseToken()->authenticate();

        DB::transaction(function () use ($request, $compra, $usuario) {
            foreach ($request->items as $item) {
                $detalle  = DetalleCompra::findOrFail($item['detalle_compra_id']);
                $producto = Producto::find($detalle->producto_id);

                $lote = $this->fifo->registrarEntrada(
                    producto:         $producto,
                    cantidad:         $item['cantidad_recibida'],
                    costoUnitario:    (float) $detalle->costo_unitario,
                    usuarioId:        $usuario->id,
                    compraId:         $compra->id,
                    numeroLote:       $item['numero_lote'] ?? null,
                    fechaVencimiento: $item['fecha_vencimiento'] ?? null,
                );

                $detalle->update([
                    'cantidad_recibida' => $detalle->cantidad_recibida + $item['cantidad_recibida'],
                    'lote_generado_id'  => $lote->id,
                ]);
            }

            $todoRecibido = $compra->detalles->every(
                fn ($d) => $d->fresh()->cantidad_recibida >= $d->cantidad_ordenada
            );

            $compra->update([
                'estado'          => $todoRecibido ? 'recibida' : 'parcial',
                'fecha_recepcion' => now(),
            ]);
        });

        return response()->json([
            'success' => true,
            'data'    => $compra->fresh()->load(['detalles.producto', 'detalles.loteGenerado']),
        ]);
    }

    private function generarCodigo(): string
    {
        $fecha       = now()->format('Ymd');
        $correlativo = Compra::whereDate('fecha_orden', today())->count() + 1;

        return "OC-{$fecha}-" . str_pad($correlativo, 3, '0', STR_PAD_LEFT);
    }
}
