<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriasSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            ['nombre' => 'Bebidas Calientes',       'aplica_a' => 'ambos'],
            ['nombre' => 'Bebidas Frías',            'aplica_a' => 'ambos'],
            ['nombre' => 'Repostería',               'aplica_a' => 'ambos'],
            ['nombre' => 'Alimentos',                'aplica_a' => 'ambos'],
            ['nombre' => 'Insumos / Materia Prima',  'aplica_a' => 'producto'],
        ];

        DB::table('categorias')->insertOrIgnore($categorias);
    }
}
