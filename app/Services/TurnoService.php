<?php

namespace App\Services;

use App\Models\CierreDiario;
use App\Models\CorteCaja;
use App\Models\Turno;
use App\Services\ContabilidadService;
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

        return DB::transaction(function () use ($usuarioId, $cajaInicial) {
            $turno = Turno::create([
                'usuario_apertura' => $usuarioId,
                'caja_inicial'     => $cajaInicial,
                'estado'           => 'abierto',
            ]);

            $fecha = now()->format('Ymd');
            $turno->codigo = "T-{$fecha}-" . str_pad($turno->id, 4, '0', STR_PAD_LEFT);
            $turno->save();

            return $turno;
        });
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
            $ventasCompletadas = $turno->ventas()->where('estado', 'completada');

            $totalVentas = (clone $ventasCompletadas)->sum('total');

            $efectivoEsperado = (clone $ventasCompletadas)->sum('pago_efectivo');

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
                'efectivo_esperado'     => $efectivoEsperado,
                'billetes_200'          => $datosCorte['billetes_200'] ?? 0,
                'billetes_100'          => $datosCorte['billetes_100'] ?? 0,
                'billetes_50'           => $datosCorte['billetes_50']  ?? 0,
                'billetes_20'           => $datosCorte['billetes_20']  ?? 0,
                'billetes_10'           => $datosCorte['billetes_10']  ?? 0,
                'monedas_total'         => $datosCorte['monedas_total'] ?? 0,
                'total_efectivo_contado'=> $datosCorte['total_efectivo_contado'],
                'total_tarjeta'         => $datosCorte['total_tarjeta'] ?? 0,
                'total_transferencia'   => $datosCorte['total_transferencia'] ?? 0,
                'total_qr'              => $datosCorte['total_qr'] ?? 0,
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
        $totalVentasQr         = 0;
        $totalDescuentos       = 0;
        $totalImpuestos        = 0;
        $numVentas             = 0;

        foreach ($turnosDelDia as $t) {
            foreach ($t->ventas()->where('estado', 'completada')->get() as $venta) {
                $totalVentas  += $venta->total;
                $totalDescuentos += $venta->descuento;
                $totalImpuestos  += $venta->impuesto;
                $numVentas++;

                // Usar columnas pago_* individuales para contar cada método
                $totalVentasEfectivo      += (float) ($venta->pago_efectivo ?? 0);
                $totalVentasTarjeta       += (float) ($venta->pago_tarjeta ?? 0);
                $totalVentasTransfer      += (float) ($venta->pago_transferencia ?? 0);
                $totalVentasQr            += (float) ($venta->pago_qr ?? 0);
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
            'total_ventas_qr'            => $totalVentasQr,
            'total_descuentos'           => $totalDescuentos,
            'total_impuestos'            => $totalImpuestos,
            'total_gastos_operativos'    => $totalGastos,
            'num_ventas'                 => $numVentas,
            'num_turnos'                 => $turnosDelDia->count(),
        ]);

        // Vincular turno al cierre
        $turno->update(['cierre_diario_id' => $cierre->id]);

        // Generar asiento de cierre contable si el cierre tiene datos
        if ($totalVentas > 0 || $totalGastos > 0) {
            try {
                app(ContabilidadService::class)->ejecutarCierreDiario($cierre);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
