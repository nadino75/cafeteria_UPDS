<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContenidoPantalla extends Model
{
    protected $table = 'contenido_pantalla';

    protected $fillable = [
        'titulo',
        'tipo',
        'archivo_url',
        'duracion_segundos',
        'activo',
        'orden',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'duracion_segundos' => 'integer',
        'orden' => 'integer',
    ];
}
