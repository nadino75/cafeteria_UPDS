<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LineaAsiento extends Model
{
    protected $table = 'lineas_asiento';

    protected $fillable = ['asiento_id', 'cuenta_id', 'tipo', 'monto', 'descripcion'];

    public $timestamps = false;

    protected $casts = [
        'monto' => 'decimal:2',
    ];

    public function asiento()
    {
        return $this->belongsTo(AsientoContable::class, 'asiento_id');
    }

    public function cuenta()
    {
        return $this->belongsTo(CuentaContable::class, 'cuenta_id');
    }
}
