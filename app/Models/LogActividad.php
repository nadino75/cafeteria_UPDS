<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogActividad extends Model
{
    protected $table = 'log_actividad';

    protected $fillable = [
        'usuario_id',
        'accion',
        'modulo',
        'descripcion',
        'ip_address',
    ];

    public $timestamps = false;

    protected $casts = [
        'fecha' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
