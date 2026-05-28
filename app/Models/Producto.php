<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $table = 'productos';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'nombre',
        'codigo',
        'categoria_id',
        'precio_venta',
        'costo_unitario',
        'stock_actual',
        'stock_minimo',
        'unidad_medida',
        'requiere_lote',
        'activo',
    ];

    protected $casts = [
        'precio_venta'   => 'decimal:2',
        'costo_unitario' => 'decimal:2',
        'requiere_lote'  => 'boolean',
        'activo'         => 'boolean',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function lotes()
    {
        return $this->hasMany(LoteInventario::class, 'producto_id');
    }

    public function lotesDisponibles()
    {
        return $this->hasMany(LoteInventario::class, 'producto_id')
                    ->where('estado', 'disponible')
                    ->orderBy('fecha_entrada', 'asc');
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoInventario::class, 'producto_id');
    }

    public function menuIngredientes()
    {
        return $this->hasMany(MenuIngrediente::class, 'producto_id');
    }
}
