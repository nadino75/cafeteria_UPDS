<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Compra extends Model
{
    protected $table = 'compras';

    protected $fillable = [
        'codigo',
        'proveedor_id',
        'usuario_id',
        'fecha_recepcion',
        'subtotal',
        'impuesto',
        'total',
        'estado',
        'nota',
    ];

    public $timestamps = false;

    protected $casts = [
        'fecha_orden'     => 'datetime',
        'fecha_recepcion' => 'datetime',
        'subtotal'        => 'decimal:2',
        'impuesto'        => 'decimal:2',
        'total'           => 'decimal:2',
    ];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleCompra::class, 'compra_id');
    }

    public function lotes()
    {
        return $this->hasMany(LoteInventario::class, 'compra_id');
    }
}
