<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'email',
        'telefono',
        'puntos_acumulados',
        'puntos_canjeados',
    ];

    public $timestamps = false;

    protected $casts = [
        'fecha_registro' => 'datetime',
    ];

    public function ventas()
    {
        return $this->hasMany(Venta::class, 'cliente_id');
    }
}
