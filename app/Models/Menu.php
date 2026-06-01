<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $table = 'menus';

    protected $fillable = [
        'nombre',
        'descripcion',
        'categoria_id',
        'precio_venta',
        'tipo',
        'costo',
        'imagen_url',
        'disponible_desde',
        'disponible_hasta',
        'activo',
    ];

    public $timestamps = false;

    protected $casts = [
        'precio_venta' => 'decimal:2',
        'costo'        => 'decimal:2',
        'activo'       => 'boolean',
        'created_at'   => 'datetime',
    ];

    public function scopePreparados($q)
    {
        return $q->where('tipo', 'preparado');
    }

    public function scopeDirectos($q)
    {
        return $q->where('tipo', 'directo');
    }

    public function esDirecto(): bool
    {
        return $this->tipo === 'directo';
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function ingredientes()
    {
        return $this->hasMany(MenuIngrediente::class, 'menu_id');
    }

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'menu_ingredientes', 'menu_id', 'producto_id')
                    ->withPivot('cantidad', 'unidad_medida');
    }
}
