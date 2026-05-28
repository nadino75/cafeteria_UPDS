<?php

namespace App\Services;

use App\Models\CierreDiario;
use App\Models\CorteCaja;
use App\Models\Turno;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TurnoService
{
    /**
     * Abre un nuevo turno para el usuario. Falla si ya tiene uno abierto.
     */
    public function abrirTurno(int $usuarioId, float $cajaInicial): Turno
    {
        $turnoActivo = Turno::where('usuario_apertura', $usuarioId)
            ->where('estado', 'abierto')
            ->first();

        if ($turnoActivo) {
            throw new \RuntimeException('Ya existe un turno abierto para este usuario.');
        }

        return Turno::create([
            'codigo'          => $this->generarCodigo(),
            'usuario_apertura'=> $usuarioId,
            'caja_inicial'    => $cajaInicial,
            'estado'          => 'abierto',
        ]);
    }

    /**
     * Cierra el turno: calcula totales, crea corte de caja y consolida cierre diario.
     */
    public function cerrarTurno(Turno $turno, array $datosCorte, int $usuarioId): CorteCaja
    {
        if ($turno->estado !== 'abierto' && $turno->estado !== 'en_corte') {
            throw new \RuntimeException('El turno ya está cerrado.');
        }

        return DB::transaction(function () use ($turno, $datosCorte, $usuarioId) {
            // Calcular totales del turno
            $totalVentas = $turno->ventas()
                ->where('estado', 'completada')
                ->sum('total');

            $totalGastos = $turno->gastos()->sum('monto');

            $cajaFinalEsperada = $turno->caja_inicial + $totalVentas - $totalGastos;
            $totalReal = (float) $datosCorte['total_real'];

            $turno->update([
                'total_ventas_esperado' => $totalVentas,
                'total_gastos_turno'    => $totalGastos,
                'caja_final_esperada'   => $cajaFinalEsperada,
                'caja_final_real'       => $totalReal,
                'estado'                => 'cerrado',
                'usuario_cierre'        => $usuarioId,
                'fecha_cierre'          => now(),
            ]);

            $corte = CorteCaja::create([
                'turno_id'              => $turno->id,
                'usuario_id'            => $usuarioId,
                'billetes_200'          => $datosCorte['billetes_200'] ?? 0,
                'billetes_100'          => $datosCorte['billetes_100'] ?? 0,
                'billetes_50'           => $datosCorte['billetes_50']  ?? 0,
                'billetes_20'           => $datosCorte['billetes_20']  ?? 0,
                'billetes_10'           => $datosCorte['billetes_10']  ?? 0,
                'monedas_total'         => $datosCorte['monedas_total'] ?? 0,
                'total_efectivo_contado'=> $datosCorte['total_efectivo_contado'],
                'total_tarjeta'         => $datosCorte['total_tarjeta'] ?? 0,
                'total_transferencia'   => $datosCorte['total_transferencia'] ?? 0,
                'total_real'            => $totalReal,
                'observaciones'         => $datosCorte['observaciones'] ?? null,
            ]);

            $this->consolidarCierreDiario($turno, $usuarioId);

            return $corte;
        });
    }

    /**
     * Consolida o actualiza el cierre diario del día del turno.
     */
    private function consolidarCierreDiario(Turno $turno, int $usuarioId): void
    {
        $fecha = Carbon::parse($turno->fecha_apertura)->toDateString();

        $cierre = CierreDiario::firstOrCreate(
            ['fecha' => $fecha],
            ['usuario_id' => $usuarioId, 'estado' => 'borrador']
        );

        // Recalcular todos los turnos del día
        $turnosDelDia = Turno::where('estado', 'cerrado')
            ->whereDate('fecha_apertura', $fecha)
            ->with('ventas')
            ->get();

        $totalVentas           = 0;
        $totalVentasEfectivo   = 0;
        $totalVentasTarjeta    = 0;
        $totalVentasTransfer   = 0;
        $totalDescuentos       = 0;
        $totalImpuestos        = 0;
        $numVentas             = 0;

        foreach ($turnosDelDia as $t) {
            foreach ($t->ventas()->where('estado', 'completada')->get() as $venta) {
                $totalVentas  += $venta->total;
                $totalDescuentos += $venta->descuento;
                $totalImpuestos  += $venta->impuesto;
                $numVentas++;

                match ($venta->metodo_pago) {
                    'efectivo'     => $totalVentasEfectivo += $venta->total,
                    'tarjeta'      => $totalVentasTarjeta  += $venta->total,
                    'transferencia'=> $totalVentasTransfer += $venta->total,
                    default        => null,
                };
            }
        }

        $totalGastos = \App\Models\GastoOperativo::whereIn(
            'turno_id', $turnosDelDia->pluck('id')
        )->sum('monto');

        $cierre->update([
            'total_ventas'               => $totalVentas,
            'total_ventas_efectivo'      => $totalVentasEfectivo,
            'total_ventas_tarjeta'       => $totalVentasTarjeta,
            'total_ventas_transferencia' => $totalVentasTransfer,
            'total_descuentos'           => $totalDescuentos,
            'total_impuestos'            => $totalImpuestos,
            'total_gastos_operativos'    => $totalGastos,
            'num_ventas'                 => $numVentas,
            'num_turnos'                 => $turnosDelDia->count(),
        ]);

        // Vincular turno al cierre
        $turno->update(['cierre_diario_id' => $cierre->id]);
    }

    private function generarCodigo(): string
    {
        $fecha     = now()->format('Ymd');
        $correlativo = Turno::whereDate('fecha_apertura', today())->count() + 1;

        return "T-{$fecha}-" . str_pad($correlativo, 3, '0', STR_PAD_LEFT);
    }
}
