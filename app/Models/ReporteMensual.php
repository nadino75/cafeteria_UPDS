<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReporteMensual extends Model
{
    protected $table = 'reporte_mensual';

    protected $fillable = [
        'anio',
        'mes',
        'total_ventas',
        'total_costo_mercancia',
        'total_gastos_operativos',
        'utilidad_bruta',
        'utilidad_neta',
        'num_ventas',
        'ticket_promedio',
        'producto_mas_vendido',
    ];

    public $timestamps = false;

    protected $casts = [
        'total_ventas'            => 'decimal:2',
        'total_costo_mercancia'   => 'decimal:2',
        'total_gastos_operativos' => 'decimal:2',
        'utilidad_bruta'          => 'decimal:2',
        'utilidad_neta'           => 'decimal:2',
        'ticket_promedio'         => 'decimal:2',
        'generado_en'             => 'datetime',
    ];
}
