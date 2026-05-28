<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccionSistema extends Model
{
    protected $table = 'acciones_sistema';

    protected $fillable = ['clave', 'nombre'];

    public $timestamps = false;
}
