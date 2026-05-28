<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleVenta extends Model
{
    protected $table = 'detalle_venta';

    protected $fillable = [
        'venta_id',
        'tipo_item',
        'producto_id',
        'menu_id',
        'cantidad',
        'precio_unitario',
        'descuento_item',
        'subtotal',
        'costo_fifo',
    ];

    public $timestamps = false;

    protected $casts = [
        'precio_unitario' => 'decimal:2',
        'descuento_item'  => 'decimal:2',
        'subtotal'        => 'decimal:2',
        'costo_fifo'      => 'decimal:2',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }

    public function lotes()
    {
        return $this->hasMany(DetalleVentaLote::class, 'detalle_venta_id');
    }
}
