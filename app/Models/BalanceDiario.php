<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BalanceDiario extends Model
{
    protected $table = 'balance_diario';

    protected $fillable = [
        'fecha',
        'cierre_diario_id',
        'ingresos_ventas',
        'otros_ingresos',
        'total_ingresos',
        'costo_mercancia_vendida',
        'gastos_operativos',
        'gastos_nomina',
        'otros_gastos',
        'total_egresos',
    ];

    public $timestamps = false;

    protected $casts = [
        'fecha'                   => 'date',
        'ingresos_ventas'         => 'decimal:2',
        'otros_ingresos'          => 'decimal:2',
        'total_ingresos'          => 'decimal:2',
        'costo_mercancia_vendida' => 'decimal:2',
        'gastos_operativos'       => 'decimal:2',
        'gastos_nomina'           => 'decimal:2',
        'otros_gastos'            => 'decimal:2',
        'total_egresos'           => 'decimal:2',
    ];

    public function cierreDiario()
    {
        return $this->belongsTo(CierreDiario::class, 'cierre_diario_id');
    }

    public function getUtilidadBrutaAttribute(): float
    {
        return (float) $this->ingresos_ventas - (float) $this->costo_mercancia_vendida;
    }

    public function getUtilidadNetaAttribute(): float
    {
        return (float) $this->total_ingresos - (float) $this->total_egresos;
    }
}
