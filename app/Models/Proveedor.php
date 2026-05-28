<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    protected $table = 'proveedores';

    protected $fillable = [
        'nombre_empresa',
        'contacto_nombre',
        'email',
        'telefono',
        'direccion',
        'activo',
    ];

    public $timestamps = false;

    protected $casts = [
        'activo'     => 'boolean',
        'created_at' => 'datetime',
    ];

    public function compras()
    {
        return $this->hasMany(Compra::class, 'proveedor_id');
    }
}
