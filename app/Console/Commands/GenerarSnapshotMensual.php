<?php

namespace App\Console\Commands;

use App\Models\GastoOperativo;
use App\Models\ReporteMensual;
use App\Models\Venta;
use App\Services\ContabilidadService;
use Illuminate\Console\Command;

class GenerarSnapshotMensual extends Command
{
    protected $signature = 'contabilidad:snapshot {--anio=} {--mes=}';
    protected $description = 'Genera o actualiza el snapshot mensual de contabilidad';

    public function handle(ContabilidadService $contabilidad): int
    {
        $anio = (int) ($this->option('anio') ?? now()->year);
        $mes  = (int) ($this->option('mes') ?? now()->month);

        $this->info("Generando snapshot para {$anio}-{$mes}...");

        $desde = now()->setYear($anio)->setMonth($mes)->startOfMonth();
        $hasta = now()->setYear($anio)->setMonth($mes)->endOfMonth();

        $ventas = Venta::where('estado', 'completada')
            ->whereDate('fecha', '>=', $desde->toDateString())
            ->whereDate('fecha', '<=', $hasta->toDateString());

        $totalVentas       = (float) $ventas->sum('total');
        $totalCosto        = (float) $ventas->sum('costo_total');
        $numVentas         = $ventas->count();
        $ticketPromedio    = $numVentas > 0 ? round($totalVentas / $numVentas, 2) : 0;

        $totalGastos = (float) GastoOperativo::whereDate('fecha', '>=', $desde->toDateString())
            ->whereDate('fecha', '<=', $hasta->toDateString())
            ->sum('monto');

        $productoTop = \DB::table('detalle_venta as dv')
            ->join('ventas as v', 'dv.venta_id', '=', 'v.id')
            ->join('productos as p', 'dv.producto_id', '=', 'p.id')
            ->where('v.estado', 'completada')
            ->whereDate('v.fecha', '>=', $desde->toDateString())
            ->whereDate('v.fecha', '<=', $hasta->toDateString())
            ->whereNotNull('dv.producto_id')
            ->groupBy('p.id', 'p.nombre')
            ->orderByDesc(\DB::raw('SUM(dv.cantidad)'))
            ->select('p.nombre')
            ->first();

        ReporteMensual::updateOrCreate(
            ['anio' => $anio, 'mes' => $mes],
            [
                'total_ventas'          => $totalVentas,
                'total_costo_mercancia' => $totalCosto,
                'total_gastos_operativos'=> $totalGastos,
                'utilidad_bruta'        => $totalVentas - $totalCosto,
                'utilidad_neta'         => $totalVentas - $totalCosto - $totalGastos,
                'num_ventas'            => $numVentas,
                'ticket_promedio'       => $ticketPromedio,
                'producto_mas_vendido'  => $productoTop?->nombre,
            ]
        );

        $this->info("Snapshot {$anio}-{$mes} completado.");
        $this->table(
            ['Concepto', 'Valor'],
            [
                ['Ventas', "Bs. {$totalVentas}"],
                ['CMV', "Bs. {$totalCosto}"],
                ['Gastos', "Bs. {$totalGastos}"],
                ['Utilidad Neta', "Bs. " . ($totalVentas - $totalCosto - $totalGastos)],
                ['Ventas', $numVentas],
                ['Ticket Promedio', "Bs. {$ticketPromedio}"],
            ]
        );

        return self::SUCCESS;
    }
}
