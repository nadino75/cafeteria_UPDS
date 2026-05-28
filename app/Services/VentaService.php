<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\DetalleVenta;
use App\Models\DetalleVentaLote;
use App\Models\Menu;
use App\Models\Producto;
use App\Models\Turno;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;

class VentaService
{
    public function __construct(private FifoService $fifo) {}

    /**
     * Registra una venta completa con descuento FIFO.
     *
     * $items: [['tipo' => 'producto'|'menu', 'id' => int, 'cantidad' => int, 'precio_unitario' => float, 'descuento' => float]]
     */
    public function registrarVenta(
        int $turnoId,
        int $usuarioId,
        array $items,
        string $metodoPago,
        ?int $clienteId = null,
        float $descuentoGlobal = 0,
        float $impuesto = 0,
        ?string $nota = null
    ): Venta {
        return DB::transaction(function () use (
            $turnoId, $usuarioId, $items, $metodoPago,
            $clienteId, $descuentoGlobal, $impuesto, $nota
        ) {
            $turno = Turno::lockForUpdate()->findOrFail($turnoId);

            if (!$turno->estaAbierto()) {
                throw new \RuntimeException('No existe un turno abierto para esta operación.');
            }

            // Validar stock antes de hacer cualquier descuento
            $this->validarStock($items);

            $subtotal   = 0;
            $costoTotal = 0;
            $detalles   = [];

            foreach ($items as $item) {
                $precioUnit = (float) $item['precio_unitario'];
                $cantidad   = (int)   $item['cantidad'];
                $descItem   = (float) ($item['descuento'] ?? 0);
                $subtotalItem = ($precioUnit * $cantidad) - $descItem;

                $subtotal += $subtotalItem;

                $detalles[] = [
                    'tipo_item'      => $item['tipo'],
                    'producto_id'    => $item['tipo'] === 'producto' ? $item['id'] : null,
                    'menu_id'        => $item['tipo'] === 'menu'     ? $item['id'] : null,
                    'cantidad'       => $cantidad,
                    'precio_unitario'=> $precioUnit,
                    'descuento_item' => $descItem,
                    'subtotal'       => $subtotalItem,
                    'productos_a_descontar' => $this->resolverProductosADescontar($item),
                ];
            }

            $total = $subtotal - $descuentoGlobal + $impuesto;

            $venta = Venta::create([
                'turno_id'   => $turnoId,
                'usuario_id' => $usuarioId,
                'cliente_id' => $clienteId,
                'subtotal'   => $subtotal,
                'descuento'  => $descuentoGlobal,
                'impuesto'   => $impuesto,
                'total'      => $total,
                'metodo_pago'=> $metodoPago,
                'nota'       => $nota,
            ]);

            // Registrar detalles y descontar FIFO
            foreach ($detalles as $detalle) {
                $productosDescontar = $detalle['productos_a_descontar'];
                unset($detalle['productos_a_descontar']);

                $detalleVenta = DetalleVenta::create(array_merge(
                    $detalle,
                    ['venta_id' => $venta->id, 'costo_fifo' => 0]
                ));

                $costoDetalle = 0;
                foreach ($productosDescontar as $pd) {
                    $producto = Producto::find($pd['producto_id']);
                    $result   = $this->fifo->descontarInventario(
                        $producto,
                        $pd['cantidad'],
                        $usuarioId,
                        'venta',
                        $venta->id
                    );

                    $costoDetalle += $result['costo_total'];

                    foreach ($result['lotes_consumidos'] as $lc) {
                        DetalleVentaLote::create([
                            'detalle_venta_id'    => $detalleVenta->id,
                            'lote_id'             => $lc['lote_id'],
                            'cantidad_consumida'  => $lc['cantidad_consumida'],
                            'costo_unitario_lote' => $lc['costo_unitario_lote'],
                        ]);
                    }
                }

                $detalleVenta->update(['costo_fifo' => $costoDetalle]);
                $costoTotal += $costoDetalle;
            }

            $venta->update(['costo_total' => $costoTotal]);

            // Sumar puntos al cliente (1 punto por cada unidad monetaria)
            if ($clienteId) {
                Cliente::find($clienteId)?->increment('puntos_acumulados', (int) $total);
            }

            return $venta->load('detalles');
        });
    }

    private function validarStock(array $items): void
    {
        foreach ($items as $item) {
            $productosADescontar = $this->resolverProductosADescontar($item);

            foreach ($productosADescontar as $pd) {
                if (!$this->fifo->hayStockSuficiente($pd['producto_id'], $pd['cantidad'])) {
                    $nombre = Producto::find($pd['producto_id'])?->nombre ?? "ID {$pd['producto_id']}";
                    throw new \RuntimeException("Stock insuficiente para '{$nombre}'.");
                }
            }
        }
    }

    /**
     * Devuelve los pares [producto_id, cantidad] que hay que descontar del inventario
     * para un ítem de venta (puede ser producto directo o menú con ingredientes).
     */
    private function resolverProductosADescontar(array $item): array
    {
        if ($item['tipo'] === 'producto') {
            return [['producto_id' => $item['id'], 'cantidad' => (int) $item['cantidad']]];
        }

        // Menú: descontar los ingredientes
        $menu = Menu::with('ingredientes')->find($item['id']);
        $resultado = [];

        foreach ($menu->ingredientes as $ing) {
            $resultado[] = [
                'producto_id' => $ing->producto_id,
                'cantidad'    => (int) ceil($ing->cantidad * $item['cantidad']),
            ];
        }

        return $resultado;
    }
}
