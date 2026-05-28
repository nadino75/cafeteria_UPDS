<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $table = 'ventas';

    protected $fillable = [
        'turno_id',
        'usuario_id',
        'cliente_id',
        'subtotal',
        'descuento',
        'impuesto',
        'total',
        'costo_total',
        'metodo_pago',
        'estado',
        'nota',
    ];

    public $timestamps = false;

    protected $casts = [
        'fecha'       => 'datetime',
        'subtotal'    => 'decimal:2',
        'descuento'   => 'decimal:2',
        'impuesto'    => 'decimal:2',
        'total'       => 'decimal:2',
        'costo_total' => 'decimal:2',
    ];

    public function turno()
    {
        return $this->belongsTo(Turno::class, 'turno_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleVenta::class, 'venta_id');
    }
}
