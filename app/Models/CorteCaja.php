<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CorteCaja extends Model
{
    protected $table = 'cortes_caja';

    protected $fillable = [
        'turno_id',
        'usuario_id',
        'billetes_200',
        'billetes_100',
        'billetes_50',
        'billetes_20',
        'billetes_10',
        'monedas_total',
        'total_efectivo_contado',
        'total_tarjeta',
        'total_transferencia',
        'total_real',
        'observaciones',
    ];

    public $timestamps = false;

    protected $casts = [
        'fecha_corte'           => 'datetime',
        'monedas_total'         => 'decimal:2',
        'total_efectivo_contado'=> 'decimal:2',
        'total_tarjeta'         => 'decimal:2',
        'total_transferencia'   => 'decimal:2',
        'total_real'            => 'decimal:2',
    ];

    public function turno()
    {
        return $this->belongsTo(Turno::class, 'turno_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
