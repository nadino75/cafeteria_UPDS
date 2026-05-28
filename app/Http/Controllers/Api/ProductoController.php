<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Producto::with('categoria');

        if ($request->has('activo')) {
            $query->where('activo', filter_var($request->activo, FILTER_VALIDATE_BOOLEAN));
        }
        if ($request->has('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }
        if ($request->has('stock_bajo')) {
            $query->whereColumn('stock_actual', '<=', 'stock_minimo');
        }

        return response()->json([
            'success' => true,
            'data'    => $query->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre'         => 'required|string|max:100',
            'codigo'         => 'nullable|string|max:50|unique:productos',
            'categoria_id'   => 'required|exists:categorias,id',
            'precio_venta'   => 'required|numeric|min:0',
            'costo_unitario' => 'required|numeric|min:0',
            'stock_minimo'   => 'integer|min:0',
            'unidad_medida'  => 'string|max:20',
            'requiere_lote'  => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $producto = Producto::create($request->only([
            'nombre', 'codigo', 'categoria_id', 'precio_venta',
            'costo_unitario', 'stock_minimo', 'unidad_medida', 'requiere_lote',
        ]));

        return response()->json(['success' => true, 'data' => $producto->load('categoria')], 201);
    }

    public function show(Producto $producto): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $producto->load(['categoria', 'lotesDisponibles']),
        ]);
    }

    public function update(Request $request, Producto $producto): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre'         => 'string|max:100',
            'codigo'         => 'nullable|string|max:50|unique:productos,codigo,' . $producto->id,
            'categoria_id'   => 'exists:categorias,id',
            'precio_venta'   => 'numeric|min:0',
            'costo_unitario' => 'numeric|min:0',
            'stock_minimo'   => 'integer|min:0',
            'unidad_medida'  => 'string|max:20',
            'activo'         => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $producto->update($request->only([
            'nombre', 'codigo', 'categoria_id', 'precio_venta',
            'costo_unitario', 'stock_minimo', 'unidad_medida', 'activo',
        ]));

        return response()->json(['success' => true, 'data' => $producto->load('categoria')]);
    }

    public function destroy(Producto $producto): JsonResponse
    {
        $producto->update(['activo' => false]);

        return response()->json(['success' => true, 'message' => 'Producto desactivado.']);
    }
}
