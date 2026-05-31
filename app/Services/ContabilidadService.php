<?php

namespace App\Services;

use App\Models\AsientoContable;
use App\Models\BalanceDiario;
use App\Models\CierreDiario;
use App\Models\Compra;
use App\Models\CuentaContable;
use App\Models\GastoOperativo;
use App\Models\LineaAsiento;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;

class ContabilidadService
{
    private ?CuentaContable $caja;
    private ?CuentaContable $ingresosVentas;
    private ?CuentaContable $costoMercancia;
    private ?CuentaContable $inventario;
    private ?CuentaContable $cuentasPagar;
    private ?CuentaContable $gastosOperacion;
    private ?CuentaContable $gastosNomina;
    private ?CuentaContable $gastosMantenimiento;
    private ?CuentaContable $gastosServicios;
    private ?CuentaContable $otrosIngresos;

    public function __construct()
    {
        $this->caja              = CuentaContable::where('codigo', '1100')->first();
        $this->ingresosVentas    = CuentaContable::where('codigo', '4100')->first();
        $this->costoMercancia    = CuentaContable::where('codigo', '5100')->first();
        $this->inventario        = CuentaContable::where('codigo', '1200')->first();
        $this->cuentasPagar      = CuentaContable::where('codigo', '2100')->first();
        $this->gastosOperacion   = CuentaContable::where('codigo', '5200')->first();
        $this->gastosNomina      = CuentaContable::where('codigo', '5300')->first();
        $this->gastosMantenimiento = CuentaContable::where('codigo', '5400')->first();
        $this->gastosServicios   = CuentaContable::where('codigo', '5500')->first();
        $this->otrosIngresos     = CuentaContable::where('codigo', '4200')->first();
    }

    private function generarNumeroAsiento(): string
    {
        $anio = now()->format('Y');
        $ultimo = AsientoContable::where('numero_asiento', 'like', "AS-{$anio}-%")
            ->orderBy('numero_asiento', 'desc')
            ->first();

        $secuencial = $ultimo ? (int) substr($ultimo->numero_asiento, -4) + 1 : 1;
        return "AS-{$anio}-" . str_pad($secuencial, 4, '0', STR_PAD_LEFT);
    }

    public function generarAsientoVenta(Venta $venta): AsientoContable
    {
        $desc = "Venta #{$venta->id} - {$venta->metodo_pago}";

        return DB::transaction(function () use ($venta, $desc) {
            $asiento = AsientoContable::create([
                'fecha'        => $venta->fecha?->toDateString() ?? now()->toDateString(),
                'numero_asiento' => $this->generarNumeroAsiento(),
                'descripcion'  => $desc,
                'usuario_id'   => $venta->usuario_id,
                'estado'       => 'confirmado',
            ]);

            $total   = (float) $venta->total;
            $costo   = (float) ($venta->costo_total ?? 0);

            // Débito: Caja (ingreso del cliente)
            LineaAsiento::create([
                'asiento_id'  => $asiento->id,
                'cuenta_id'   => $this->caja->id,
                'tipo'        => 'debito',
                'monto'       => $total,
                'descripcion' => "Ingreso por venta #{$venta->id}",
            ]);

            // Crédito: Ingresos por Ventas
            LineaAsiento::create([
                'asiento_id'  => $asiento->id,
                'cuenta_id'   => $this->ingresosVentas->id,
                'tipo'        => 'credito',
                'monto'       => $total,
                'descripcion' => "Venta #{$venta->id}",
            ]);

            if ($costo > 0) {
                // Débito: Costo de Mercancía Vendida
                LineaAsiento::create([
                    'asiento_id'  => $asiento->id,
                    'cuenta_id'   => $this->costoMercancia->id,
                    'tipo'        => 'debito',
                    'monto'       => $costo,
                    'descripcion' => "CMV venta #{$venta->id}",
                ]);

                // Crédito: Inventario
                LineaAsiento::create([
                    'asiento_id'  => $asiento->id,
                    'cuenta_id'   => $this->inventario->id,
                    'tipo'        => 'credito',
                    'monto'       => $costo,
                    'descripcion' => "Salida inventario venta #{$venta->id}",
                ]);
            }

            return $asiento->load('lineas');
        });
    }

    public function generarAsientoGasto(GastoOperativo $gasto): AsientoContable
    {
        $cuentaGasto = match ($gasto->categoria) {
            'nomina'       => $this->gastosNomina,
            'mantenimiento'=> $this->gastosMantenimiento,
            'servicios'    => $this->gastosServicios,
            default        => $this->gastosOperacion,
        };

        $desc = "Gasto: {$gasto->descripcion}";

        return DB::transaction(function () use ($gasto, $cuentaGasto, $desc) {
            $asiento = AsientoContable::create([
                'fecha'         => now()->toDateString(),
                'numero_asiento'=> $this->generarNumeroAsiento(),
                'descripcion'   => $desc,
                'usuario_id'    => $gasto->usuario_id,
                'estado'        => 'confirmado',
            ]);

            LineaAsiento::create([
                'asiento_id'  => $asiento->id,
                'cuenta_id'   => $cuentaGasto->id,
                'tipo'        => 'debito',
                'monto'       => (float) $gasto->monto,
                'descripcion' => $gasto->descripcion,
            ]);

            LineaAsiento::create([
                'asiento_id'  => $asiento->id,
                'cuenta_id'   => $this->caja->id,
                'tipo'        => 'credito',
                'monto'       => (float) $gasto->monto,
                'descripcion' => "Pago: {$gasto->descripcion}",
            ]);

            return $asiento->load('lineas');
        });
    }

    public function generarAsientoCompra(Compra $compra): AsientoContable
    {
        $total = (float) $compra->total;

        $desc = "Compra #{$compra->id} a proveedor #{$compra->proveedor_id}";

        return DB::transaction(function () use ($compra, $total, $desc) {
            $asiento = AsientoContable::create([
                'fecha'         => $compra->fecha_recepcion?->toDateString() ?? now()->toDateString(),
                'numero_asiento'=> $this->generarNumeroAsiento(),
                'descripcion'   => $desc,
                'usuario_id'    => $compra->usuario_id,
                'estado'        => 'confirmado',
            ]);

            LineaAsiento::create([
                'asiento_id'  => $asiento->id,
                'cuenta_id'   => $this->inventario->id,
                'tipo'        => 'debito',
                'monto'       => $total,
                'descripcion' => "Compra #{$compra->id}",
            ]);

            LineaAsiento::create([
                'asiento_id'  => $asiento->id,
                'cuenta_id'   => $this->cuentasPagar->id,
                'tipo'        => 'credito',
                'monto'       => $total,
                'descripcion' => "Cuenta por pagar compra #{$compra->id}",
            ]);

            return $asiento->load('lineas');
        });
    }

    public function ejecutarCierreDiario(CierreDiario $cierre): BalanceDiario
    {
        return DB::transaction(function () use ($cierre) {
            $balance = BalanceDiario::firstOrNew(['fecha' => $cierre->fecha]);
            $balance->cierre_diario_id = $cierre->id;
            $balance->ingresos_ventas  = (float) $cierre->total_ventas;
            $balance->otros_ingresos   = 0;
            $balance->total_ingresos   = (float) $cierre->total_ventas;

            $cmv = DB::table('ventas')
                ->whereDate('fecha', $cierre->fecha)
                ->where('estado', '!=', 'anulada')
                ->sum('costo_total');

            $balance->costo_mercancia_vendida = (float) $cmv;
            $balance->gastos_operativos       = (float) $cierre->total_gastos_operativos;
            $balance->gastos_nomina           = (float) GastoOperativo::whereDate('fecha', $cierre->fecha)
                ->where('categoria', 'nomina')->sum('monto');
            $balance->otros_gastos            = 0;
            $balance->total_egresos           = (float) $cmv + (float) $cierre->total_gastos_operativos;

            $balance->save();

            // Crear asiento de cierre
            $asiento = AsientoContable::create([
                'cierre_diario_id' => $cierre->id,
                'fecha'            => $cierre->fecha,
                'numero_asiento'   => $this->generarNumeroAsiento(),
                'descripcion'      => "Cierre diario {$cierre->fecha}",
                'usuario_id'       => $cierre->usuario_id,
                'estado'           => 'confirmado',
            ]);

            // Cierre: liquidar Ingresos por Ventas vs Caja
            $totalVentas = (float) $cierre->total_ventas;
            if ($totalVentas > 0) {
                LineaAsiento::create([
                    'asiento_id' => $asiento->id,
                    'cuenta_id'  => $this->ingresosVentas->id,
                    'tipo'       => 'debito',
                    'monto'      => $totalVentas,
                    'descripcion'=> "Cierre ingresos {$cierre->fecha}",
                ]);
                LineaAsiento::create([
                    'asiento_id' => $asiento->id,
                    'cuenta_id'  => $this->caja->id,
                    'tipo'       => 'credito',
                    'monto'      => $totalVentas,
                    'descripcion'=> "Cierre ingresos {$cierre->fecha}",
                ]);
            }

            $totalGastos = (float) $cierre->total_gastos_operativos;
            if ($totalGastos > 0) {
                LineaAsiento::create([
                    'asiento_id' => $asiento->id,
                    'cuenta_id'  => $this->gastosOperacion->id,
                    'tipo'       => 'credito',
                    'monto'      => $totalGastos,
                    'descripcion'=> "Cierre gastos {$cierre->fecha}",
                ]);
                LineaAsiento::create([
                    'asiento_id' => $asiento->id,
                    'cuenta_id'  => $this->caja->id,
                    'tipo'       => 'debito',
                    'monto'      => $totalGastos,
                    'descripcion'=> "Cierre gastos {$cierre->fecha}",
                ]);
            }

            return $balance;
        });
    }

    public function obtenerTendenciaMensual(int $meses = 6, ?int $hastaAnio = null, ?int $hastaMes = null): array
    {
        $data = [];
        $referencia = $hastaAnio && $hastaMes
            ? now()->setYear($hastaAnio)->setMonth($hastaMes)->endOfMonth()
            : now();

        for ($i = $meses - 1; $i >= 0; $i--) {
            $fecha = (clone $referencia)->subMonths($i);
            $anio  = (int) $fecha->format('Y');
            $mes   = (int) $fecha->format('m');

            $ventas = Venta::whereYear('fecha', $anio)
                ->whereMonth('fecha', $mes)
                ->where('estado', '!=', 'anulada');

            $totalVentas  = (float) $ventas->sum('total');
            $totalCostos  = (float) $ventas->sum('costo_total');
            $numVentas    = $ventas->count();
            $totalGastos  = (float) GastoOperativo::whereYear('fecha', $anio)
                ->whereMonth('fecha', $mes)->sum('monto');

            $data[] = [
                'anio'         => $anio,
                'mes'          => $mes,
                'etiqueta'     => $fecha->isoFormat('MMM YYYY'),
                'ingresos'     => $totalVentas,
                'costos'       => $totalCostos,
                'utilidad_bruta' => $totalVentas - $totalCostos,
                'gastos'       => $totalGastos,
                'utilidad_neta' => $totalVentas - $totalCostos - $totalGastos,
                'num_ventas'   => $numVentas,
                'ticket_promedio' => $numVentas > 0 ? round($totalVentas / $numVentas, 2) : 0,
            ];
        }
        return $data;
    }

    public function obtenerPyG(?string $desde = null, ?string $hasta = null): array
    {
        $desde = $desde ?? now()->startOfMonth()->toDateString();
        $hasta = $hasta ?? now()->toDateString();

        $ventas = Venta::whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta)
            ->where('estado', '!=', 'anulada');

        $ingresosVentas  = (float) $ventas->sum('total');
        $costoVentas     = (float) $ventas->sum('costo_total');
        $numVentas       = $ventas->count();

        $gastosPorCategoria = GastoOperativo::whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta)
            ->groupBy('categoria')
            ->selectRaw('categoria, SUM(monto) as total')
            ->pluck('total', 'categoria')
            ->toArray();

        $totalGastos = array_sum($gastosPorCategoria);

        return [
            'periodo'       => compact('desde', 'hasta'),
            'ingresos'      => [
                'ventas'           => $ingresosVentas,
                'otros_ingresos'   => 0,
                'total_ingresos'   => $ingresosVentas,
            ],
            'costos'        => [
                'costo_ventas'     => $costoVentas,
                'utilidad_bruta'   => $ingresosVentas - $costoVentas,
                'margen_bruto_pct' => $ingresosVentas > 0
                    ? round(($ingresosVentas - $costoVentas) / $ingresosVentas * 100, 1)
                    : 0,
            ],
            'gastos'        => [
                'detalle'          => $gastosPorCategoria,
                'total_gastos'     => $totalGastos,
            ],
            'resultado'     => [
                'utilidad_neta'    => $ingresosVentas - $costoVentas - $totalGastos,
                'margen_neto_pct'  => $ingresosVentas > 0
                    ? round(($ingresosVentas - $costoVentas - $totalGastos) / $ingresosVentas * 100, 1)
                    : 0,
            ],
            'num_ventas'    => $numVentas,
            'ticket_promedio' => $numVentas > 0 ? round($ingresosVentas / $numVentas, 2) : 0,
        ];
    }

    public function obtenerComparativa(): array
    {
        $actual   = $this->obtenerTendenciaMensual(2);
        $mesActual   = $actual[1] ?? null;
        $mesAnterior = $actual[0] ?? null;

        if (!$mesActual || !$mesAnterior) {
            return [];
        }

        $diffIngresos = $mesAnterior['ingresos'] > 0
            ? round(($mesActual['ingresos'] - $mesAnterior['ingresos']) / $mesAnterior['ingresos'] * 100, 1)
            : 0;
        $diffUtilidad = $mesAnterior['utilidad_neta'] > 0
            ? round(($mesActual['utilidad_neta'] - $mesAnterior['utilidad_neta']) / abs($mesAnterior['utilidad_neta']) * 100, 1)
            : 0;

        return [
            'actual'             => $mesActual,
            'anterior'           => $mesAnterior,
            'variacion_ingresos' => $diffIngresos,
            'variacion_utilidad' => $diffUtilidad,
        ];
    }

    public function obtenerBalanceGeneral(): array
    {
        $saldoCaja       = (float) DB::table('lineas_asiento')
            ->join('asientos_contables', 'lineas_asiento.asiento_id', '=', 'asientos_contables.id')
            ->where('lineas_asiento.cuenta_id', $this->caja->id)
            ->where('asientos_contables.estado', 'confirmado')
            ->select(DB::raw('SUM(CASE WHEN lineas_asiento.tipo = "debito" THEN lineas_asiento.monto ELSE -lineas_asiento.monto END) as saldo'))
            ->value('saldo') ?? 0;

        $saldoInventario = (float) DB::table('lineas_asiento')
            ->join('asientos_contables', 'lineas_asiento.asiento_id', '=', 'asientos_contables.id')
            ->where('lineas_asiento.cuenta_id', $this->inventario->id)
            ->where('asientos_contables.estado', 'confirmado')
            ->select(DB::raw('SUM(CASE WHEN lineas_asiento.tipo = "debito" THEN lineas_asiento.monto ELSE -lineas_asiento.monto END) as saldo'))
            ->value('saldo') ?? 0;

        $saldoPagar = (float) DB::table('lineas_asiento')
            ->join('asientos_contables', 'lineas_asiento.asiento_id', '=', 'asientos_contables.id')
            ->where('lineas_asiento.cuenta_id', $this->cuentasPagar->id)
            ->where('asientos_contables.estado', 'confirmado')
            ->select(DB::raw('SUM(CASE WHEN lineas_asiento.tipo = "credito" THEN lineas_asiento.monto ELSE -lineas_asiento.monto END) as saldo'))
            ->value('saldo') ?? 0;

        $totalActivo = $saldoCaja + $saldoInventario;

        return [
            'activos' => [
                'caja'       => round($saldoCaja, 2),
                'inventario' => round($saldoInventario, 2),
                'total'      => round($totalActivo, 2),
            ],
            'pasivos' => [
                'cuentas_pagar' => round($saldoPagar, 2),
                'total'         => round($saldoPagar, 2),
            ],
            'patrimonio' => [
                'total' => round($totalActivo - $saldoPagar, 2),
            ],
        ];
    }
}
