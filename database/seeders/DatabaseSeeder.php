<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ModulosSistemaSeeder::class,
            AccionesSistemaSeeder::class,
            RolesSeeder::class,
            CuentasContablesSeeder::class,
            CategoriasSeeder::class,
            UsuarioAdminSeeder::class,
            RolPermisosSeeder::class,
        ]);
    }
}
