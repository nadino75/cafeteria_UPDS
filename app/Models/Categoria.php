<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $table = 'categorias';

    protected $fillable = ['nombre', 'descripcion', 'aplica_a'];

    public $timestamps = false;

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function productos()
    {
        return $this->hasMany(Producto::class, 'categoria_id');
    }

    public function menus()
    {
        return $this->hasMany(Menu::class, 'categoria_id');
    }
}
