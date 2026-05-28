<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CuentasContablesSeeder extends Seeder
{
    public function run(): void
    {
        $cuentas = [
            ['codigo' => '4100', 'nombre' => 'Ingresos por Ventas',           'tipo' => 'ingreso'],
            ['codigo' => '4200', 'nombre' => 'Otros Ingresos',                'tipo' => 'ingreso'],
            ['codigo' => '5100', 'nombre' => 'Costo de Mercancía Vendida',    'tipo' => 'egreso'],
            ['codigo' => '5200', 'nombre' => 'Gastos de Operación',           'tipo' => 'egreso'],
            ['codigo' => '5300', 'nombre' => 'Gastos de Personal / Nómina',   'tipo' => 'egreso'],
            ['codigo' => '5400', 'nombre' => 'Gastos de Mantenimiento',       'tipo' => 'egreso'],
            ['codigo' => '5500', 'nombre' => 'Gastos de Servicios',           'tipo' => 'egreso'],
            ['codigo' => '1100', 'nombre' => 'Caja / Efectivo',               'tipo' => 'activo'],
            ['codigo' => '1200', 'nombre' => 'Inventario de Mercancías',      'tipo' => 'activo'],
            ['codigo' => '2100', 'nombre' => 'Cuentas por Pagar Proveedores', 'tipo' => 'pasivo'],
        ];

        DB::table('cuentas_contables')->insertOrIgnore($cuentas);
    }
}
