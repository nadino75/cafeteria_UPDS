<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleVentaLote extends Model
{
    protected $table = 'detalle_venta_lotes';

    protected $fillable = [
        'detalle_venta_id',
        'lote_id',
        'cantidad_consumida',
        'costo_unitario_lote',
    ];

    public $timestamps = false;

    protected $casts = [
        'costo_unitario_lote' => 'decimal:2',
    ];

    public function detalleVenta()
    {
        return $this->belongsTo(DetalleVenta::class, 'detalle_venta_id');
    }

    public function lote()
    {
        return $this->belongsTo(LoteInventario::class, 'lote_id');
    }
}
