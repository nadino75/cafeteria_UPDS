<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoriaController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => Categoria::orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre'     => 'required|string|max:50',
            'descripcion'=> 'nullable|string',
            'aplica_a'   => 'in:producto,menu,ambos',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $categoria = Categoria::create($request->only(['nombre', 'descripcion', 'aplica_a']));

        return response()->json(['success' => true, 'data' => $categoria], 201);
    }

    public function show(Categoria $categoria): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $categoria]);
    }

    public function update(Request $request, Categoria $categoria): JsonResponse
    {
        $categoria->update($request->only(['nombre', 'descripcion', 'aplica_a']));

        return response()->json(['success' => true, 'data' => $categoria]);
    }

    public function destroy(Categoria $categoria): JsonResponse
    {
        if ($categoria->productos()->exists() || $categoria->menus()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar: tiene productos o menús asociados.',
            ], 409);
        }

        $categoria->delete();

        return response()->json(['success' => true, 'message' => 'Categoría eliminada.']);
    }
}
