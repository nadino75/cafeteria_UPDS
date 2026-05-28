<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoteInventario extends Model
{
    protected $table = 'lotes_inventario';

    protected $fillable = [
        'producto_id',
        'compra_id',
        'numero_lote',
        'fecha_vencimiento',
        'cantidad_inicial',
        'cantidad_disponible',
        'costo_unitario',
        'estado',
    ];

    public $timestamps = false;

    protected $casts = [
        'fecha_entrada'      => 'datetime',
        'fecha_vencimiento'  => 'date',
        'costo_unitario'     => 'decimal:2',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function compra()
    {
        return $this->belongsTo(Compra::class, 'compra_id');
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoInventario::class, 'lote_id');
    }

    public function scopeDisponibles($query)
    {
        return $query->where('estado', 'disponible')->orderBy('fecha_entrada', 'asc');
    }
}
