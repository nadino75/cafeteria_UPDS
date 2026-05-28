<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolPermiso extends Model
{
    protected $table = 'rol_permisos';

    protected $fillable = ['rol_id', 'modulo_id', 'accion_id'];

    public $timestamps = false;

    public function rol()
    {
        return $this->belongsTo(Role::class, 'rol_id');
    }

    public function modulo()
    {
        return $this->belongsTo(ModuloSistema::class, 'modulo_id');
    }

    public function accion()
    {
        return $this->belongsTo(AccionSistema::class, 'accion_id');
    }
}
