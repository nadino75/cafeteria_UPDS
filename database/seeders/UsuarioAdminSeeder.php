<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsuarioAdminSeeder extends Seeder
{
    public function run(): void
    {
        $rolAdmin = DB::table('roles')->where('nombre', 'Administrador')->first();

        if (!$rolAdmin) {
            return;
        }

        DB::table('usuarios')->insertOrIgnore([
            'nombre_completo' => 'Administrador',
            'email'           => 'admin@cafeteria.upds',
            'password_hash'   => Hash::make('Admin1234!'),
            'rol_id'          => $rolAdmin->id,
            'activo'          => true,
        ]);
    }
}
