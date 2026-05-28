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
        'imagen_url',
        'disponible_desde',
        'disponible_hasta',
        'activo',
    ];

    public $timestamps = false;

    protected $casts = [
        'precio_venta' => 'decimal:2',
        'activo'       => 'boolean',
        'created_at'   => 'datetime',
    ];

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
