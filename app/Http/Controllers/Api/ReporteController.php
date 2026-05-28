<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BalanceDiario;
use App\Models\CierreDiario;
use App\Models\ReporteMensual;
use App\Models\ReporteVentasProducto;
use App\Models\Venta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReporteController extends Controller
{
    public function ventasDiarias(Request $request): JsonResponse
    {
        $fecha = $request->get('fecha', today()->toDateString());

        $ventas = Venta::whereDate('fecha', $fecha)
            ->where('estado', 'completada')
            ->with(['usuario', 'cliente'])
            ->get();

        $resumen = [
            'fecha'           => $fecha,
            'total_ventas'    => $ventas->sum('total'),
            'total_costo'     => $ventas->sum('costo_total'),
            'utilidad_bruta'  => $ventas->sum('total') - $ventas->sum('costo_total'),
            'num_transacciones'=> $ventas->count(),
            'ticket_promedio' => $ventas->count() > 0
                ? round($ventas->sum('total') / $ventas->count(), 2)
                : 0,
            'por_metodo_pago' => $ventas->groupBy('metodo_pago')->map->sum('total'),
        ];

        return response()->json(['success' => true, 'data' => $resumen]);
    }

    public function productosMasVendidos(Request $request): JsonResponse
    {
        $desde = $request->get('desde', today()->startOfMonth()->toDateString());
        $hasta = $request->get('hasta', today()->toDateString());

        $productos = DB::table('detalle_venta as dv')
            ->join('ventas as v', 'dv.venta_id', '=', 'v.id')
            ->join('productos as p', 'dv.producto_id', '=', 'p.id')
            ->select(
                'p.id',
                'p.nombre',
                DB::raw('SUM(dv.cantidad) as total_vendido'),
                DB::raw('SUM(dv.subtotal) as ingresos'),
                DB::raw('SUM(dv.costo_fifo) as costo_total')
            )
            ->where('v.estado', 'completada')
            ->whereDate('v.fecha', '>=', $desde)
            ->whereDate('v.fecha', '<=', $hasta)
            ->whereNotNull('dv.producto_id')
            ->groupBy('p.id', 'p.nombre')
            ->orderByDesc('total_vendido')
            ->limit(20)
            ->get();

        return response()->json(['success' => true, 'data' => $productos]);
    }

    public function balanceDiario(Request $request): JsonResponse
    {
        $fecha = $request->get('fecha', today()->toDateString());

        $balance = BalanceDiario::with('cierreDiario')
            ->where('fecha', $fecha)
            ->first();

        return response()->json(['success' => true, 'data' => $balance]);
    }

    public function resumenMensual(Request $request): JsonResponse
    {
        $anio = $request->get('anio', now()->year);
        $mes  = $request->get('mes', now()->month);

        $reporte = ReporteMensual::where('anio', $anio)->where('mes', $mes)->first();

        if (!$reporte) {
            // Calcular en tiempo real si no hay snapshot
            $desde = now()->setYear($anio)->setMonth($mes)->startOfMonth()->toDateString();
            $hasta = now()->setYear($anio)->setMonth($mes)->endOfMonth()->toDateString();

            $ventas = Venta::where('estado', 'completada')
                ->whereDate('fecha', '>=', $desde)
                ->whereDate('fecha', '<=', $hasta)
                ->get();

            $reporte = [
                'anio'              => $anio,
                'mes'               => $mes,
                'total_ventas'      => $ventas->sum('total'),
                'total_costo_mercancia' => $ventas->sum('costo_total'),
                'num_ventas'        => $ventas->count(),
                'ticket_promedio'   => $ventas->count() > 0
                    ? round($ventas->sum('total') / $ventas->count(), 2)
                    : 0,
            ];
        }

        return response()->json(['success' => true, 'data' => $reporte]);
    }

    public function cierresDiarios(Request $request): JsonResponse
    {
        $query = CierreDiario::with('usuario');

        if ($request->has('mes') && $request->has('anio')) {
            $query->whereYear('fecha', $request->anio)
                  ->whereMonth('fecha', $request->mes);
        }

        return response()->json([
            'success' => true,
            'data'    => $query->orderBy('fecha', 'desc')->get(),
        ]);
    }
}
