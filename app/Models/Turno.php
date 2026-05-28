<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Turno extends Model
{
    protected $table = 'turnos';

    protected $fillable = [
        'codigo',
        'usuario_apertura',
        'usuario_cierre',
        'caja_inicial',
        'total_ventas_esperado',
        'total_gastos_turno',
        'caja_final_esperada',
        'caja_final_real',
        'observaciones',
        'estado',
        'cierre_diario_id',
        'fecha_cierre',
    ];

    public $timestamps = false;

    protected $casts = [
        'fecha_apertura'         => 'datetime',
        'fecha_cierre'           => 'datetime',
        'caja_inicial'           => 'decimal:2',
        'total_ventas_esperado'  => 'decimal:2',
        'total_gastos_turno'     => 'decimal:2',
        'caja_final_esperada'    => 'decimal:2',
        'caja_final_real'        => 'decimal:2',
    ];

    public function usuarioApertura()
    {
        return $this->belongsTo(Usuario::class, 'usuario_apertura');
    }

    public function usuarioCierre()
    {
        return $this->belongsTo(Usuario::class, 'usuario_cierre');
    }

    public function cierreDiario()
    {
        return $this->belongsTo(CierreDiario::class, 'cierre_diario_id');
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class, 'turno_id');
    }

    public function corteCaja()
    {
        return $this->hasOne(CorteCaja::class, 'turno_id');
    }

    public function gastos()
    {
        return $this->hasMany(GastoOperativo::class, 'turno_id');
    }

    public function estaAbierto(): bool
    {
        return $this->estado === 'abierto';
    }
}
