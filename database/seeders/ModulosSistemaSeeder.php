<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModulosSistemaSeeder extends Seeder
{
    public function run(): void
    {
        $modulos = [
            ['clave' => 'ventas',       'nombre' => 'Punto de Venta'],
            ['clave' => 'inventario',   'nombre' => 'Inventario y Stock'],
            ['clave' => 'compras',      'nombre' => 'Compras a Proveedores'],
            ['clave' => 'menus',        'nombre' => 'Gestión de Menús'],
            ['clave' => 'usuarios',     'nombre' => 'Usuarios y Roles'],
            ['clave' => 'reportes',     'nombre' => 'Reportes y Estadísticas'],
            ['clave' => 'contabilidad', 'nombre' => 'Contabilidad Diaria'],
            ['clave' => 'turnos',       'nombre' => 'Turnos y Cierres de Caja'],
            ['clave' => 'clientes',     'nombre' => 'Clientes'],
            ['clave' => 'gastos',       'nombre' => 'Gastos Operativos'],
        ];

        DB::table('modulos_sistema')->insertOrIgnore($modulos);
    }
}
