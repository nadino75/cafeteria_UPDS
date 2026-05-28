<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModuloSistema extends Model
{
    protected $table = 'modulos_sistema';

    protected $fillable = ['clave', 'nombre', 'descripcion'];

    public $timestamps = false;

    public function permisos()
    {
        return $this->hasMany(RolPermiso::class, 'modulo_id');
    }
}
