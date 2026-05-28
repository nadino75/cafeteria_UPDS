<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccionesSistemaSeeder extends Seeder
{
    public function run(): void
    {
        $acciones = [
            ['clave' => 'crear',    'nombre' => 'Crear / Registrar'],
            ['clave' => 'leer',     'nombre' => 'Ver / Consultar'],
            ['clave' => 'editar',   'nombre' => 'Editar / Actualizar'],
            ['clave' => 'eliminar', 'nombre' => 'Eliminar'],
            ['clave' => 'exportar', 'nombre' => 'Exportar / Imprimir'],
            ['clave' => 'aprobar',  'nombre' => 'Aprobar / Confirmar'],
        ];

        DB::table('acciones_sistema')->insertOrIgnore($acciones);
    }
}
