<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'roles';

    protected $fillable = [
        'nombre',
        'descripcion',
        'es_superadmin',
        'activo',
    ];

    public $timestamps = false;

    protected $casts = [
        'es_superadmin' => 'boolean',
        'activo'        => 'boolean',
        'created_at'    => 'datetime',
    ];

    public function usuarios()
    {
        return $this->hasMany(Usuario::class, 'rol_id');
    }

    public function permisos()
    {
        return $this->hasMany(RolPermiso::class, 'rol_id');
    }

    public function modulos()
    {
        return $this->belongsToMany(
            ModuloSistema::class,
            'rol_permisos',
            'rol_id',
            'modulo_id'
        )->withPivot('accion_id');
    }
}
