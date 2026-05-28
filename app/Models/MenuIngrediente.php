<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuIngrediente extends Model
{
    protected $table = 'menu_ingredientes';

    protected $fillable = ['menu_id', 'producto_id', 'cantidad', 'unidad_medida'];

    public $timestamps = false;

    protected $casts = [
        'cantidad' => 'decimal:3',
    ];

    public function menu()
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
