<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CuentaContable extends Model
{
    protected $table = 'cuentas_contables';

    protected $fillable = ['codigo', 'nombre', 'tipo', 'descripcion', 'activo'];

    public $timestamps = false;

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function lineas()
    {
        return $this->hasMany(LineaAsiento::class, 'cuenta_id');
    }
}
