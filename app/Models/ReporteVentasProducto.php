<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReporteVentasProducto extends Model
{
    protected $table = 'reporte_ventas_producto';

    protected $fillable = [
        'fecha',
        'producto_id',
        'menu_id',
        'nombre_item',
        'cantidad_vendida',
        'ingresos_total',
        'costo_total_fifo',
    ];

    public $timestamps = false;

    protected $casts = [
        'fecha'          => 'date',
        'ingresos_total' => 'decimal:2',
        'costo_total_fifo' => 'decimal:2',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }

    public function getMargenBrutoAttribute(): float
    {
        return (float) $this->ingresos_total - (float) $this->costo_total_fifo;
    }
}
