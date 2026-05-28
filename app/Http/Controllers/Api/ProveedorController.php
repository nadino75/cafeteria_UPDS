<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proveedor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProveedorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Proveedor::query();

        if ($request->has('activo')) {
            $query->where('activo', filter_var($request->activo, FILTER_VALIDATE_BOOLEAN));
        }

        return response()->json([
            'success' => true,
            'data'    => $query->orderBy('nombre_empresa')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre_empresa'  => 'required|string|max:100',
            'contacto_nombre' => 'nullable|string|max:100',
            'email'           => 'nullable|email|unique:proveedores',
            'telefono'        => 'nullable|string|max:20',
            'direccion'       => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $proveedor = Proveedor::create($request->only([
            'nombre_empresa', 'contacto_nombre', 'email', 'telefono', 'direccion',
        ]));

        return response()->json(['success' => true, 'data' => $proveedor], 201);
    }

    public function show(Proveedor $proveedor): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $proveedor]);
    }

    public function update(Request $request, Proveedor $proveedor): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email'  => 'nullable|email|unique:proveedores,email,' . $proveedor->id,
            'activo' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $proveedor->update($request->only([
            'nombre_empresa', 'contacto_nombre', 'email', 'telefono', 'direccion', 'activo',
        ]));

        return response()->json(['success' => true, 'data' => $proveedor]);
    }

    public function destroy(Proveedor $proveedor): JsonResponse
    {
        $proveedor->update(['activo' => false]);

        return response()->json(['success' => true, 'message' => 'Proveedor desactivado.']);
    }
}
