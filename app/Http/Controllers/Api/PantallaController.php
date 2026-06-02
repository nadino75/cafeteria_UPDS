<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContenidoPantalla;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PantallaController extends Controller
{
    /**
     * Endpoint público — solo contenidos activos ordenados
     */
    public function publicos()
    {
        $contenidos = ContenidoPantalla::where('activo', true)
            ->orderBy('orden')
            ->get(['id', 'tipo', 'archivo_url', 'duracion_segundos']);

        return response()->json($contenidos);
    }

    /**
     * Lista completa para gestión (admin/gerente)
     */
    public function index()
    {
        $contenidos = ContenidoPantalla::orderBy('orden')->get();
        return response()->json($contenidos);
    }

    /**
     * Subir nuevo contenido
     */
    public function store(Request $request)
    {
        $request->validate([
            'titulo'  => 'required|string|max:255',
            'tipo'    => 'required|in:video,imagen',
            'archivo' => 'required|file|mimes:mp4,jpeg,png|max:102400',
            'duracion_segundos' => 'required|integer|min:3|max:3600',
        ]);

        $archivo = $request->file('archivo');
        $extension = $archivo->getClientOriginalExtension();
        $nombre = uniqid() . '_' . Str::slug($request->titulo) . '.' . $extension;
        $ruta = $archivo->storeAs('pantalla', $nombre, 'public');

        $maxOrden = ContenidoPantalla::max('orden') ?? 0;

        $contenido = ContenidoPantalla::create([
            'titulo'           => $request->titulo,
            'tipo'             => $request->tipo,
            'archivo_url'      => Storage::disk('public')->url($ruta),
            'duracion_segundos' => $request->duracion_segundos,
            'orden'            => $maxOrden + 1,
        ]);

        return response()->json($contenido, 201);
    }

    /**
     * Editar metadatos (no permite cambiar archivo)
     */
    public function update(Request $request, ContenidoPantalla $pantalla)
    {
        $request->validate([
            'titulo'           => 'sometimes|string|max:255',
            'duracion_segundos' => 'sometimes|integer|min:3|max:3600',
            'orden'            => 'sometimes|integer|min:0',
        ]);

        $pantalla->update($request->only([
            'titulo', 'duracion_segundos', 'orden'
        ]));

        return response()->json($pantalla);
    }

    /**
     * Activar/desactivar toggle
     */
    public function toggle(ContenidoPantalla $pantalla)
    {
        $pantalla->update(['activo' => !$pantalla->activo]);
        return response()->json($pantalla);
    }

    /**
     * Reordenar masivamente
     */
    public function reordenar(Request $request)
    {
        $request->validate([
            'ordenes' => 'required|array',
            'ordenes.*.id' => 'required|exists:contenido_pantalla,id',
            'ordenes.*.orden' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->ordenes as $item) {
                ContenidoPantalla::where('id', $item['id'])->update(['orden' => $item['orden']]);
            }
        });

        return response()->noContent();
    }

    /**
     * Eliminar contenido y su archivo
     */
    public function destroy(ContenidoPantalla $pantalla)
    {
        $baseUrl = Storage::disk('public')->url('');
        $pathRel = str_replace($baseUrl, '', $pantalla->archivo_url);
        if ($pathRel && $pathRel !== $pantalla->archivo_url && Storage::disk('public')->exists($pathRel)) {
            Storage::disk('public')->delete($pathRel);
        }

        $pantalla->delete();

        return response()->noContent();
    }
}
