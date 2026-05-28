<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsientoContable extends Model
{
    protected $table = 'asientos_contables';

    protected $fillable = [
        'cierre_diario_id',
        'fecha',
        'numero_asiento',
        'descripcion',
        'usuario_id',
        'estado',
    ];

    public $timestamps = false;

    protected $casts = [
        'fecha'      => 'date',
        'created_at' => 'datetime',
    ];

    public function cierreDiario()
    {
        return $this->belongsTo(CierreDiario::class, 'cierre_diario_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function lineas()
    {
        return $this->hasMany(LineaAsiento::class, 'asiento_id');
    }
}
