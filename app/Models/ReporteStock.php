<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReporteStock extends Model
{
    protected $table = 'reporte_stock';

    protected $fillable = [
        'producto_id',
        'stock_actual',
        'stock_minimo',
        'alerta_stock_bajo',
        'lotes_proximos_vencer',
        'valor_inventario',
    ];

    public $timestamps = false;

    protected $casts = [
        'generado_en'        => 'datetime',
        'alerta_stock_bajo'  => 'boolean',
        'valor_inventario'   => 'decimal:2',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
