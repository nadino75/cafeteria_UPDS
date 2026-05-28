<?php

namespace App\Services;

use App\Models\DetalleVenta;
use App\Models\DetalleVentaLote;
use App\Models\LoteInventario;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;

class FifoService
{
    /**
     * Descuenta inventario FIFO para un producto y registra los lotes consumidos.
     * Debe llamarse dentro de una transacción DB.
     *
     * @return array{costo_total: float, lotes_consumidos: array}
     * @throws \RuntimeException si el stock es insuficiente
     */
    public function descontarInventario(
        Producto $producto,
        int $cantidad,
        int $usuarioId,
        string $referenciaTipo,
        int $referenciaId
    ): array {
        $restante      = $cantidad;
        $costoTotal    = 0.0;
        $lotesConsumos = [];

        $lotes = LoteInventario::where('producto_id', $producto->id)
            ->where('estado', 'disponible')
            ->orderBy('fecha_entrada', 'asc')
            ->lockForUpdate()
            ->get();

        foreach ($lotes as $lote) {
            if ($restante <= 0) {
                break;
            }

            $consumir = min($restante, $lote->cantidad_disponible);
            $costo    = $consumir * (float) $lote->costo_unitario;

            $lote->cantidad_disponible -= $consumir;
            $lote->estado = $lote->cantidad_disponible <= 0 ? 'agotado' : 'disponible';
            $lote->save();

            $costoTotal += $costo;
            $restante   -= $consumir;

            $lotesConsumos[] = [
                'lote_id'             => $lote->id,
                'cantidad_consumida'  => $consumir,
                'costo_unitario_lote' => $lote->costo_unitario,
            ];

            MovimientoInventario::create([
                'producto_id'     => $producto->id,
                'lote_id'         => $lote->id,
                'tipo'            => 'salida',
                'cantidad'        => $consumir,
                'costo_unitario'  => $lote->costo_unitario,
                'motivo'          => 'Venta FIFO',
                'usuario_id'      => $usuarioId,
                'referencia_tipo' => $referenciaTipo,
                'referencia_id'   => $referenciaId,
            ]);
        }

        if ($restante > 0) {
            throw new \RuntimeException(
                "Stock insuficiente para '{$producto->nombre}'. Faltan {$restante} unidades."
            );
        }

        $producto->decrement('stock_actual', $cantidad);

        return [
            'costo_total'     => $costoTotal,
            'lotes_consumidos'=> $lotesConsumos,
        ];
    }

    /**
     * Registra la entrada de un nuevo lote (al recibir una compra).
     */
    public function registrarEntrada(
        Producto $producto,
        int $cantidad,
        float $costoUnitario,
        int $usuarioId,
        ?int $compraId = null,
        ?string $numeroLote = null,
        ?string $fechaVencimiento = null
    ): LoteInventario {
        $lote = LoteInventario::create([
            'producto_id'        => $producto->id,
            'compra_id'          => $compraId,
            'numero_lote'        => $numeroLote,
            'fecha_vencimiento'  => $fechaVencimiento,
            'cantidad_inicial'   => $cantidad,
            'cantidad_disponible'=> $cantidad,
            'costo_unitario'     => $costoUnitario,
            'estado'             => 'disponible',
        ]);

        MovimientoInventario::create([
            'producto_id'     => $producto->id,
            'lote_id'         => $lote->id,
            'tipo'            => 'entrada',
            'cantidad'        => $cantidad,
            'costo_unitario'  => $costoUnitario,
            'motivo'          => 'Recepción de compra',
            'usuario_id'      => $usuarioId,
            'referencia_tipo' => $compraId ? 'compra' : 'ajuste_manual',
            'referencia_id'   => $compraId,
        ]);

        $producto->increment('stock_actual', $cantidad);
        // Actualizar costo promedio del producto
        $producto->update(['costo_unitario' => $costoUnitario]);

        return $lote;
    }

    /**
     * Verifica si hay stock suficiente para un producto.
     */
    public function hayStockSuficiente(int $productoId, int $cantidad): bool
    {
        $disponible = LoteInventario::where('producto_id', $productoId)
            ->where('estado', 'disponible')
            ->sum('cantidad_disponible');

        return $disponible >= $cantidad;
    }
}
