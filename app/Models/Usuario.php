<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Usuario extends Authenticatable implements JWTSubject
{
    protected $table = 'usuarios';

    protected $fillable = [
        'nombre_completo',
        'email',
        'password_hash',
        'rol_id',
        'activo',
    ];

    protected $hidden = [
        'password_hash',
    ];

    public $timestamps = false;

    protected $casts = [
        'activo' => 'boolean',
        'ultimo_login' => 'datetime',
        'fecha_creacion' => 'datetime',
    ];

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'rol_id' => $this->rol_id,
            'email'  => $this->email,
        ];
    }

    public function rol()
    {
        return $this->belongsTo(Role::class, 'rol_id');
    }

    public function logs()
    {
        return $this->hasMany(LogActividad::class, 'usuario_id');
    }

    public function ventasRealizadas()
    {
        return $this->hasMany(Venta::class, 'usuario_id');
    }

    public function turnosAbiertos()
    {
        return $this->hasMany(Turno::class, 'usuario_apertura');
    }
}
