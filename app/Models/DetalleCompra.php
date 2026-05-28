<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleCompra extends Model
{
    protected $table = 'detalle_compra';

    protected $fillable = [
        'compra_id',
        'producto_id',
        'cantidad_ordenada',
        'cantidad_recibida',
        'costo_unitario',
        'subtotal',
        'lote_generado_id',
    ];

    public $timestamps = false;

    protected $casts = [
        'costo_unitario' => 'decimal:2',
        'subtotal'       => 'decimal:2',
    ];

    public function compra()
    {
        return $this->belongsTo(Compra::class, 'compra_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function loteGenerado()
    {
        return $this->belongsTo(LoteInventario::class, 'lote_generado_id');
    }
}
