<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoInventario extends Model
{
    protected $table = 'movimientos_inventario';

    protected $fillable = [
        'producto_id',
        'lote_id',
        'tipo',
        'cantidad',
        'costo_unitario',
        'motivo',
        'usuario_id',
        'referencia_tipo',
        'referencia_id',
    ];

    public $timestamps = false;

    protected $casts = [
        'fecha'          => 'datetime',
        'costo_unitario' => 'decimal:2',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function lote()
    {
        return $this->belongsTo(LoteInventario::class, 'lote_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
