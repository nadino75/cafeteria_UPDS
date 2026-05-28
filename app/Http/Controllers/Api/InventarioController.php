<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoteInventario;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Services\FifoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class InventarioController extends Controller
{
    public function __construct(private FifoService $fifo) {}

    public function lotes(Request $request): JsonResponse
    {
        $query = LoteInventario::with('producto');

        if ($request->has('producto_id')) {
            $query->where('producto_id', $request->producto_id);
        }
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        return response()->json([
            'success' => true,
            'data'    => $query->orderBy('fecha_entrada', 'asc')->get(),
        ]);
    }

    public function movimientos(Request $request): JsonResponse
    {
        $query = MovimientoInventario::with(['producto', 'lote', 'usuario']);

        if ($request->has('producto_id')) {
            $query->where('producto_id', $request->producto_id);
        }
        if ($request->has('tipo')) {
            $query->where('tipo', $request->tipo);
        }
        if ($request->has('fecha')) {
            $query->whereDate('fecha', $request->fecha);
        }

        return response()->json([
            'success' => true,
            'data'    => $query->orderBy('fecha', 'desc')->paginate(50),
        ]);
    }

    public function ajustarStock(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'producto_id'    => 'required|exists:productos,id',
            'cantidad'       => 'required|integer|min:1',
            'tipo'           => 'required|in:entrada,ajuste,merma,devolucion',
            'motivo'         => 'required|string|max:255',
            'costo_unitario' => 'nullable|numeric|min:0',
            'numero_lote'    => 'nullable|string',
            'fecha_vencimiento' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $usuario  = JWTAuth::parseToken()->authenticate();
        $producto = Producto::findOrFail($request->producto_id);

        DB::transaction(function () use ($request, $producto, $usuario) {
            if ($request->tipo === 'entrada') {
                $this->fifo->registrarEntrada(
                    producto:         $producto,
                    cantidad:         $request->cantidad,
                    costoUnitario:    (float) ($request->costo_unitario ?? $producto->costo_unitario),
                    usuarioId:        $usuario->id,
                    numeroLote:       $request->numero_lote,
                    fechaVencimiento: $request->fecha_vencimiento,
                );
            } else {
                MovimientoInventario::create([
                    'producto_id'    => $producto->id,
                    'tipo'           => $request->tipo,
                    'cantidad'       => $request->cantidad,
                    'motivo'         => $request->motivo,
                    'usuario_id'     => $usuario->id,
                    'referencia_tipo'=> 'ajuste_manual',
                ]);

                $delta = $request->tipo === 'merma' ? -$request->cantidad : $request->cantidad;
                $producto->increment('stock_actual', $delta);
            }
        });

        return response()->json([
            'success' => true,
            'data'    => $producto->fresh(),
        ]);
    }

    public function alertasVencimiento(): JsonResponse
    {
        $alertas = LoteInventario::with('producto')
            ->where('estado', 'disponible')
            ->where('fecha_vencimiento', '<=', Carbon::now()->addDays(7))
            ->orderBy('fecha_vencimiento', 'asc')
            ->get();

        return response()->json(['success' => true, 'data' => $alertas]);
    }

    public function stockBajo(): JsonResponse
    {
        $productos = Producto::whereColumn('stock_actual', '<=', 'stock_minimo')
            ->where('activo', true)
            ->with('categoria')
            ->get();

        return response()->json(['success' => true, 'data' => $productos]);
    }
}
