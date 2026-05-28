<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MenuController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Menu::with(['categoria', 'ingredientes.producto']);

        if ($request->has('activo')) {
            $query->where('activo', filter_var($request->activo, FILTER_VALIDATE_BOOLEAN));
        }

        return response()->json([
            'success' => true,
            'data'    => $query->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre'             => 'required|string|max:100',
            'descripcion'        => 'nullable|string',
            'categoria_id'       => 'nullable|exists:categorias,id',
            'precio_venta'       => 'required|numeric|min:0',
            'imagen_url'         => 'nullable|string|max:255',
            'disponible_desde'   => 'date_format:H:i',
            'disponible_hasta'   => 'date_format:H:i',
            'ingredientes'       => 'required|array|min:1',
            'ingredientes.*.producto_id' => 'required|exists:productos,id',
            'ingredientes.*.cantidad'    => 'required|numeric|min:0.001',
            'ingredientes.*.unidad_medida' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $menu = DB::transaction(function () use ($request) {
            $menu = Menu::create($request->only([
                'nombre', 'descripcion', 'categoria_id', 'precio_venta',
                'imagen_url', 'disponible_desde', 'disponible_hasta',
            ]));

            foreach ($request->ingredientes as $ing) {
                $menu->ingredientes()->create([
                    'producto_id'  => $ing['producto_id'],
                    'cantidad'     => $ing['cantidad'],
                    'unidad_medida'=> $ing['unidad_medida'] ?? null,
                ]);
            }

            return $menu;
        });

        return response()->json([
            'success' => true,
            'data'    => $menu->load(['categoria', 'ingredientes.producto']),
        ], 201);
    }

    public function show(Menu $menu): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $menu->load(['categoria', 'ingredientes.producto']),
        ]);
    }

    public function update(Request $request, Menu $menu): JsonResponse
    {
        $menu->update($request->only([
            'nombre', 'descripcion', 'categoria_id', 'precio_venta',
            'imagen_url', 'disponible_desde', 'disponible_hasta', 'activo',
        ]));

        if ($request->has('ingredientes')) {
            DB::transaction(function () use ($request, $menu) {
                $menu->ingredientes()->delete();
                foreach ($request->ingredientes as $ing) {
                    $menu->ingredientes()->create([
                        'producto_id'  => $ing['producto_id'],
                        'cantidad'     => $ing['cantidad'],
                        'unidad_medida'=> $ing['unidad_medida'] ?? null,
                    ]);
                }
            });
        }

        return response()->json([
            'success' => true,
            'data'    => $menu->load(['categoria', 'ingredientes.producto']),
        ]);
    }

    public function destroy(Menu $menu): JsonResponse
    {
        $menu->update(['activo' => false]);

        return response()->json(['success' => true, 'message' => 'Menú desactivado.']);
    }
}
