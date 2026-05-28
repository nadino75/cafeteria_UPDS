<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CierreDiario extends Model
{
    protected $table = 'cierres_diarios';

    protected $fillable = [
        'fecha',
        'usuario_id',
        'total_ventas',
        'total_ventas_efectivo',
        'total_ventas_tarjeta',
        'total_ventas_transferencia',
        'total_descuentos',
        'total_impuestos',
        'total_compras',
        'total_gastos_operativos',
        'num_ventas',
        'num_turnos',
        'observaciones',
        'estado',
    ];

    public $timestamps = false;

    protected $casts = [
        'fecha'                       => 'date',
        'total_ventas'                => 'decimal:2',
        'total_ventas_efectivo'       => 'decimal:2',
        'total_ventas_tarjeta'        => 'decimal:2',
        'total_ventas_transferencia'  => 'decimal:2',
        'total_descuentos'            => 'decimal:2',
        'total_impuestos'             => 'decimal:2',
        'total_compras'               => 'decimal:2',
        'total_gastos_operativos'     => 'decimal:2',
        'created_at'                  => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function turnos()
    {
        return $this->hasMany(Turno::class, 'cierre_diario_id');
    }

    public function asientos()
    {
        return $this->hasMany(AsientoContable::class, 'cierre_diario_id');
    }

    public function balance()
    {
        return $this->hasOne(BalanceDiario::class, 'cierre_diario_id');
    }
}
