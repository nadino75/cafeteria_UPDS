<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolPermisosSeeder extends Seeder
{
    public function run(): void
    {
        $modulos  = DB::table('modulos_sistema')->pluck('id', 'clave');
        $acciones = DB::table('acciones_sistema')->pluck('id', 'clave');
        $roles    = DB::table('roles')->pluck('id', 'nombre');

        // Gerente: todos los módulos excepto usuarios (solo leer), sin aprobar en contabilidad
        $todasAcciones = ['crear', 'leer', 'editar', 'eliminar', 'exportar', 'aprobar'];
        $gerenteModulos = ['ventas', 'inventario', 'compras', 'menus', 'reportes',
                           'contabilidad', 'turnos', 'clientes', 'gastos'];

        foreach ($gerenteModulos as $mod) {
            foreach ($todasAcciones as $acc) {
                DB::table('rol_permisos')->insertOrIgnore([
                    'rol_id'    => $roles['Gerente'],
                    'modulo_id' => $modulos[$mod],
                    'accion_id' => $acciones[$acc],
                ]);
            }
        }
        // Gerente solo lectura en usuarios
        DB::table('rol_permisos')->insertOrIgnore([
            'rol_id'    => $roles['Gerente'],
            'modulo_id' => $modulos['usuarios'],
            'accion_id' => $acciones['leer'],
        ]);

        // Cajero: ventas, turnos, clientes (crear/leer/editar), inventario/menus (leer)
        $cajeroPermisos = [
            'ventas'     => ['crear', 'leer'],
            'turnos'     => ['crear', 'leer', 'editar', 'aprobar'],
            'clientes'   => ['crear', 'leer', 'editar'],
            'inventario' => ['leer'],
            'menus'      => ['leer'],
        ];
        foreach ($cajeroPermisos as $mod => $accs) {
            foreach ($accs as $acc) {
                DB::table('rol_permisos')->insertOrIgnore([
                    'rol_id'    => $roles['Cajero'],
                    'modulo_id' => $modulos[$mod],
                    'accion_id' => $acciones[$acc],
                ]);
            }
        }

        // Almacenista: inventario, compras, proveedores/clientes (leer)
        $almacenPermisos = [
            'inventario' => ['crear', 'leer', 'editar', 'aprobar'],
            'compras'    => ['crear', 'leer', 'editar', 'aprobar'],
            'clientes'   => ['leer'],
        ];
        foreach ($almacenPermisos as $mod => $accs) {
            foreach ($accs as $acc) {
                DB::table('rol_permisos')->insertOrIgnore([
                    'rol_id'    => $roles['Almacenista'],
                    'modulo_id' => $modulos[$mod],
                    'accion_id' => $acciones[$acc],
                ]);
            }
        }

        // Contador: contabilidad y reportes solo lectura + exportar
        $contadorPermisos = [
            'contabilidad' => ['leer', 'exportar'],
            'reportes'     => ['leer', 'exportar'],
        ];
        foreach ($contadorPermisos as $mod => $accs) {
            foreach ($accs as $acc) {
                DB::table('rol_permisos')->insertOrIgnore([
                    'rol_id'    => $roles['Contador'],
                    'modulo_id' => $modulos[$mod],
                    'accion_id' => $acciones[$acc],
                ]);
            }
        }
    }
}
