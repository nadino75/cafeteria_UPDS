<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'nombre'       => 'Administrador',
                'descripcion'  => 'Acceso total al sistema',
                'es_superadmin'=> true,
                'activo'       => true,
            ],
            [
                'nombre'       => 'Gerente',
                'descripcion'  => 'Gestión completa excepto configuración de roles',
                'es_superadmin'=> false,
                'activo'       => true,
            ],
            [
                'nombre'       => 'Cajero',
                'descripcion'  => 'Solo ventas y cierres de turno',
                'es_superadmin'=> false,
                'activo'       => true,
            ],
            [
                'nombre'       => 'Almacenista',
                'descripcion'  => 'Inventario y compras',
                'es_superadmin'=> false,
                'activo'       => true,
            ],
            [
                'nombre'       => 'Contador',
                'descripcion'  => 'Contabilidad y reportes solo lectura',
                'es_superadmin'=> false,
                'activo'       => true,
            ],
        ];

        DB::table('roles')->insertOrIgnore($roles);
    }
}
