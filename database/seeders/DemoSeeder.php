<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

/**
 * Datos de demostración para probar los 5 dashboards.
 * Ejecutar con: php artisan db:seed --class=DemoSeeder
 * Resetear con: php artisan db:seed --class=DemoSeeder (es idempotente)
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Sembrando datos de demostración...');

        $this->seedUsuarios();
        $this->seedProductos();
        $this->seedMenus();
        $this->seedProveedores();
        $this->seedClientes();
        $this->seedInventario();
        $this->seedTurnoYVentas();
        $this->seedCompras();
        $this->seedGastos();

        $this->command->info('✓ Datos de demo listos.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // USUARIOS — uno por rol
    // ─────────────────────────────────────────────────────────────────────────
    private function seedUsuarios(): void
    {
        $roles = DB::table('roles')->pluck('id', 'nombre');

        $usuarios = [
            [
                'nombre_completo' => 'María Gerente',
                'email'           => 'gerente@cafeteria.upds',
                'password_hash'   => Hash::make('Gerente123!'),
                'rol_id'          => $roles['Gerente'],
                'activo'          => true,
            ],
            [
                'nombre_completo' => 'Juan Cajero',
                'email'           => 'cajero@cafeteria.upds',
                'password_hash'   => Hash::make('Cajero123!'),
                'rol_id'          => $roles['Cajero'],
                'activo'          => true,
            ],
            [
                'nombre_completo' => 'Pedro Almacenista',
                'email'           => 'almacen@cafeteria.upds',
                'password_hash'   => Hash::make('Almacen123!'),
                'rol_id'          => $roles['Almacenista'],
                'activo'          => true,
            ],
            [
                'nombre_completo' => 'Ana Contadora',
                'email'           => 'contador@cafeteria.upds',
                'password_hash'   => Hash::make('Contador123!'),
                'rol_id'          => $roles['Contador'],
                'activo'          => true,
            ],
        ];

        foreach ($usuarios as $u) {
            DB::table('usuarios')->insertOrIgnore($u);
        }

        $this->command->line('  ✓ Usuarios de prueba creados (4 roles)');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRODUCTOS
    // ─────────────────────────────────────────────────────────────────────────
    private function seedProductos(): void
    {
        $cats = DB::table('categorias')->pluck('id', 'nombre');

        $productos = [
            // Insumos / Materia Prima — para menús
            [
                'nombre'         => 'Café en grano',
                'codigo'         => 'INS-001',
                'categoria_id'   => $cats['Insumos / Materia Prima'],
                'precio_venta'   => 0,
                'costo_unitario' => 8.50,
                'stock_actual'   => 5,      // BAJO (mínimo 10)
                'stock_minimo'   => 10,
                'unidad_medida'  => 'kg',
                'requiere_lote'  => true,
                'activo'         => true,
            ],
            [
                'nombre'         => 'Leche entera',
                'codigo'         => 'INS-002',
                'categoria_id'   => $cats['Insumos / Materia Prima'],
                'precio_venta'   => 0,
                'costo_unitario' => 4.20,
                'stock_actual'   => 3,      // BAJO (mínimo 20)
                'stock_minimo'   => 20,
                'unidad_medida'  => 'lt',
                'requiere_lote'  => true,
                'activo'         => true,
            ],
            [
                'nombre'         => 'Azúcar',
                'codigo'         => 'INS-003',
                'categoria_id'   => $cats['Insumos / Materia Prima'],
                'precio_venta'   => 0,
                'costo_unitario' => 2.50,
                'stock_actual'   => 15,
                'stock_minimo'   => 5,
                'unidad_medida'  => 'kg',
                'requiere_lote'  => false,
                'activo'         => true,
            ],
            [
                'nombre'         => 'Harina de trigo',
                'codigo'         => 'INS-004',
                'categoria_id'   => $cats['Repostería'],
                'precio_venta'   => 0,
                'costo_unitario' => 3.80,
                'stock_actual'   => 2,      // BAJO (mínimo 8)
                'stock_minimo'   => 8,
                'unidad_medida'  => 'kg',
                'requiere_lote'  => false,
                'activo'         => true,
            ],
            [
                'nombre'         => 'Chocolate en polvo',
                'codigo'         => 'INS-005',
                'categoria_id'   => $cats['Insumos / Materia Prima'],
                'precio_venta'   => 0,
                'costo_unitario' => 12.00,
                'stock_actual'   => 25,
                'stock_minimo'   => 5,
                'unidad_medida'  => 'kg',
                'requiere_lote'  => false,
                'activo'         => true,
            ],
            // Productos de venta directa
            [
                'nombre'         => 'Agua mineral 500ml',
                'codigo'         => 'BEB-001',
                'categoria_id'   => $cats['Bebidas Frías'],
                'precio_venta'   => 5.00,
                'costo_unitario' => 2.50,
                'stock_actual'   => 48,
                'stock_minimo'   => 12,
                'unidad_medida'  => 'unidad',
                'requiere_lote'  => false,
                'activo'         => true,
            ],
            [
                'nombre'         => 'Refresco lata 350ml',
                'codigo'         => 'BEB-002',
                'categoria_id'   => $cats['Bebidas Frías'],
                'precio_venta'   => 8.00,
                'costo_unitario' => 4.50,
                'stock_actual'   => 24,
                'stock_minimo'   => 6,
                'unidad_medida'  => 'unidad',
                'requiere_lote'  => false,
                'activo'         => true,
            ],
        ];

        foreach ($productos as $p) {
            DB::table('productos')->insertOrIgnore($p);
        }

        $this->command->line('  ✓ Productos creados (4 con stock bajo)');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MENÚS
    // ─────────────────────────────────────────────────────────────────────────
    private function seedMenus(): void
    {
        $cats   = DB::table('categorias')->pluck('id', 'nombre');
        $prods  = DB::table('productos')->pluck('id', 'nombre');

        $menus = [
            [
                'nombre'            => 'Café americano',
                'descripcion'       => 'Café negro doble shot',
                'categoria_id'      => $cats['Bebidas Calientes'],
                'precio_venta'      => 12.00,
                'disponible_desde'  => '07:00',
                'disponible_hasta'  => '20:00',
                'activo'            => true,
                'ingredientes' => [
                    ['producto' => 'Café en grano', 'cantidad' => 0.02,  'unidad' => 'kg'],
                    ['producto' => 'Azúcar',         'cantidad' => 0.005, 'unidad' => 'kg'],
                ],
            ],
            [
                'nombre'            => 'Café con leche',
                'descripcion'       => 'Espresso con leche vaporizada',
                'categoria_id'      => $cats['Bebidas Calientes'],
                'precio_venta'      => 15.00,
                'disponible_desde'  => '07:00',
                'disponible_hasta'  => '20:00',
                'activo'            => true,
                'ingredientes' => [
                    ['producto' => 'Café en grano', 'cantidad' => 0.018, 'unidad' => 'kg'],
                    ['producto' => 'Leche entera',  'cantidad' => 0.15,  'unidad' => 'lt'],
                    ['producto' => 'Azúcar',         'cantidad' => 0.008, 'unidad' => 'kg'],
                ],
            ],
            [
                'nombre'            => 'Chocolate caliente',
                'descripcion'       => 'Chocolate cremoso con leche',
                'categoria_id'      => $cats['Bebidas Calientes'],
                'precio_venta'      => 14.00,
                'disponible_desde'  => '07:00',
                'disponible_hasta'  => '20:00',
                'activo'            => true,
                'ingredientes' => [
                    ['producto' => 'Chocolate en polvo', 'cantidad' => 0.030, 'unidad' => 'kg'],
                    ['producto' => 'Leche entera',       'cantidad' => 0.20,  'unidad' => 'lt'],
                    ['producto' => 'Azúcar',              'cantidad' => 0.010, 'unidad' => 'kg'],
                ],
            ],
            [
                'nombre'            => 'Croissant de mantequilla',
                'descripcion'       => 'Croissant horneado del día',
                'categoria_id'      => $cats['Repostería'],
                'precio_venta'      => 10.00,
                'disponible_desde'  => '07:00',
                'disponible_hasta'  => '14:00',
                'activo'            => true,
                'ingredientes' => [
                    ['producto' => 'Harina de trigo', 'cantidad' => 0.08, 'unidad' => 'kg'],
                    ['producto' => 'Azúcar',           'cantidad' => 0.01, 'unidad' => 'kg'],
                ],
            ],
            [
                'nombre'            => 'Combo mañana',
                'descripcion'       => 'Café americano + croissant',
                'categoria_id'      => $cats['Alimentos'],
                'precio_venta'      => 20.00,
                'disponible_desde'  => '07:00',
                'disponible_hasta'  => '11:00',
                'activo'            => true,
                'ingredientes' => [
                    ['producto' => 'Café en grano',  'cantidad' => 0.020, 'unidad' => 'kg'],
                    ['producto' => 'Harina de trigo','cantidad' => 0.080, 'unidad' => 'kg'],
                    ['producto' => 'Azúcar',          'cantidad' => 0.015, 'unidad' => 'kg'],
                ],
            ],
        ];

        foreach ($menus as $m) {
            $ingredientes = $m['ingredientes'];
            unset($m['ingredientes']);

            $existente = DB::table('menus')->where('nombre', $m['nombre'])->first();
            if (!$existente) {
                $menuId = DB::table('menus')->insertGetId($m);
                foreach ($ingredientes as $ing) {
                    if (isset($prods[$ing['producto']])) {
                        DB::table('menu_ingredientes')->insertOrIgnore([
                            'menu_id'      => $menuId,
                            'producto_id'  => $prods[$ing['producto']],
                            'cantidad'     => $ing['cantidad'],
                            'unidad_medida'=> $ing['unidad'],
                        ]);
                    }
                }
            }
        }

        $this->command->line('  ✓ Menús creados con ingredientes');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PROVEEDORES
    // ─────────────────────────────────────────────────────────────────────────
    private function seedProveedores(): void
    {
        $proveedores = [
            [
                'nombre_empresa'   => 'Distribuidora Café Bolivia',
                'contacto_nombre'  => 'Carlos Mamani',
                'email'            => 'ventas@cafebolivia.com',
                'telefono'         => '70012345',
                'direccion'        => 'Av. Arce 1234, La Paz',
                'activo'           => true,
            ],
            [
                'nombre_empresa'   => 'Lácteos del Sur',
                'contacto_nombre'  => 'Rosa Quispe',
                'email'            => 'pedidos@lacteossur.com',
                'telefono'         => '71198765',
                'direccion'        => 'Zona Industrial, Cochabamba',
                'activo'           => true,
            ],
            [
                'nombre_empresa'   => 'Panadería La Estrella',
                'contacto_nombre'  => 'Luis Flores',
                'email'            => 'luis@panaderialaestrella.com',
                'telefono'         => '72234567',
                'direccion'        => 'Calle Ingavi 456',
                'activo'           => true,
            ],
            [
                'nombre_empresa'   => 'Importadora Dulces SA',
                'contacto_nombre'  => 'Silvia Torres',
                'email'            => 'compras@dulcessa.com',
                'telefono'         => '68876543',
                'direccion'        => 'Zona Sur, La Paz',
                'activo'           => true,
            ],
        ];

        foreach ($proveedores as $p) {
            DB::table('proveedores')->insertOrIgnore($p);
        }

        $this->command->line('  ✓ Proveedores creados (4)');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CLIENTES
    // ─────────────────────────────────────────────────────────────────────────
    private function seedClientes(): void
    {
        $clientes = [
            ['nombre' => 'Ana Rodríguez',   'email' => 'ana.rodriguez@upds.edu',  'telefono' => '78901234', 'puntos_acumulados' => 150, 'puntos_canjeados' => 50],
            ['nombre' => 'Carlos Vega',     'email' => 'carlos.vega@upds.edu',    'telefono' => '79012345', 'puntos_acumulados' => 80,  'puntos_canjeados' => 0],
            ['nombre' => 'Lucía Mendoza',   'email' => 'lucia.mendoza@gmail.com', 'telefono' => '70123456', 'puntos_acumulados' => 320, 'puntos_canjeados' => 100],
            ['nombre' => 'Roberto Chávez',  'email' => null,                       'telefono' => '71234567', 'puntos_acumulados' => 45,  'puntos_canjeados' => 0],
            ['nombre' => 'Patricia Lima',   'email' => 'patricia.lima@upds.edu',  'telefono' => null,        'puntos_acumulados' => 0,   'puntos_canjeados' => 0],
        ];

        foreach ($clientes as $c) {
            DB::table('clientes')->insertOrIgnore($c);
        }

        $this->command->line('  ✓ Clientes creados (5)');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // INVENTARIO — lotes FIFO (algunos próximos a vencer)
    // ─────────────────────────────────────────────────────────────────────────
    private function seedInventario(): void
    {
        $prods   = DB::table('productos')->pluck('id', 'nombre');
        $adminId = DB::table('usuarios')->where('email', 'admin@cafeteria.upds')->value('id');

        $lotes = [
            // Café en grano — lote antiguo, próximo a vencer
            [
                'producto_id'       => $prods['Café en grano'],
                'cantidad_inicial'  => 5,
                'cantidad_disponible'=> 5,
                'costo_unitario'    => 8.50,
                'costo_total'       => 42.50,
                'fecha_entrada'     => Carbon::now()->subDays(30)->toDateString(),
                'fecha_vencimiento' => Carbon::now()->addDays(3)->toDateString(), // VENCE EN 3 DÍAS
                'numero_lote'       => 'CAFE-2024-001',
                'estado'            => 'disponible',
                'usuario_id'        => $adminId,
            ],
            // Leche — vence en 2 días
            [
                'producto_id'       => $prods['Leche entera'],
                'cantidad_inicial'  => 3,
                'cantidad_disponible'=> 3,
                'costo_unitario'    => 4.20,
                'costo_total'       => 12.60,
                'fecha_entrada'     => Carbon::now()->subDays(5)->toDateString(),
                'fecha_vencimiento' => Carbon::now()->addDays(2)->toDateString(), // VENCE EN 2 DÍAS
                'numero_lote'       => 'LECHE-2024-015',
                'estado'            => 'disponible',
                'usuario_id'        => $adminId,
            ],
            // Azúcar — sin vencimiento, stock normal
            [
                'producto_id'       => $prods['Azúcar'],
                'cantidad_inicial'  => 15,
                'cantidad_disponible'=> 15,
                'costo_unitario'    => 2.50,
                'costo_total'       => 37.50,
                'fecha_entrada'     => Carbon::now()->subDays(10)->toDateString(),
                'fecha_vencimiento' => null,
                'numero_lote'       => null,
                'estado'            => 'disponible',
                'usuario_id'        => $adminId,
            ],
            // Agua mineral
            [
                'producto_id'       => $prods['Agua mineral 500ml'],
                'cantidad_inicial'  => 48,
                'cantidad_disponible'=> 48,
                'costo_unitario'    => 2.50,
                'costo_total'       => 120.00,
                'fecha_entrada'     => Carbon::now()->subDays(2)->toDateString(),
                'fecha_vencimiento' => Carbon::now()->addMonths(12)->toDateString(),
                'numero_lote'       => 'AGUA-2024-088',
                'estado'            => 'disponible',
                'usuario_id'        => $adminId,
            ],
        ];

        foreach ($lotes as $lote) {
            $existe = DB::table('lotes_inventario')
                ->where('producto_id', $lote['producto_id'])
                ->where('numero_lote', $lote['numero_lote'])
                ->exists();

            if (!$existe) {
                DB::table('lotes_inventario')->insert($lote);

                // Registrar movimiento de entrada
                DB::table('movimientos_inventario')->insert([
                    'producto_id'     => $lote['producto_id'],
                    'tipo'            => 'entrada',
                    'cantidad'        => $lote['cantidad_inicial'],
                    'costo_unitario'  => $lote['costo_unitario'],
                    'motivo'          => 'Stock inicial de demostración',
                    'usuario_id'      => $adminId,
                    'fecha'           => $lote['fecha_entrada'],
                    'referencia_tipo' => 'ajuste_manual',
                ]);
            }
        }

        $this->command->line('  ✓ Lotes de inventario creados (2 próximos a vencer)');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TURNO ABIERTO + VENTAS DE HOY
    // ─────────────────────────────────────────────────────────────────────────
    private function seedTurnoYVentas(): void
    {
        $cajeroId = DB::table('usuarios')->where('email', 'cajero@cafeteria.upds')->value('id');
        if (!$cajeroId) return;

        // Verificar que no exista ya un turno abierto para este cajero
        $turnoExistente = DB::table('turnos')
            ->where('usuario_apertura', $cajeroId)
            ->where('estado', 'abierto')
            ->first();

        if ($turnoExistente) {
            $this->command->line('  ✓ Turno ya existe (omitido)');
            return;
        }

        // Crear turno abierto
        $turnoId = DB::table('turnos')->insertGetId([
            'usuario_apertura' => $cajeroId,
            'caja_inicial'     => 200.00,
            'fecha_apertura'   => Carbon::now()->subHours(2),
            'estado'           => 'abierto',
        ]);

        // Crear ventas simuladas para el turno
        $menus   = DB::table('menus')->pluck('id', 'nombre');
        $clientes = DB::table('clientes')->pluck('id', 'nombre');

        $ventas = [
            [
                'metodo_pago' => 'efectivo',
                'cliente_id'  => null,
                'total'       => 25.00,
                'costo_total' => 8.50,
                'items'       => [['menu' => 'Combo mañana', 'cantidad' => 1, 'precio' => 20.00], ['menu' => 'Café americano', 'cantidad' => 0, 'precio' => 0]],
            ],
            [
                'metodo_pago' => 'efectivo',
                'cliente_id'  => $clientes['Ana Rodríguez'] ?? null,
                'total'       => 15.00,
                'costo_total' => 4.20,
                'items'       => [],
            ],
            [
                'metodo_pago' => 'tarjeta',
                'cliente_id'  => $clientes['Carlos Vega'] ?? null,
                'total'       => 29.00,
                'costo_total' => 9.80,
                'items'       => [],
            ],
        ];

        foreach ($ventas as $v) {
            DB::table('ventas')->insertOrIgnore([
                'turno_id'    => $turnoId,
                'usuario_id'  => $cajeroId,
                'cliente_id'  => $v['cliente_id'],
                'metodo_pago' => $v['metodo_pago'],
                'subtotal'    => $v['total'],
                'descuento'   => 0,
                'impuesto'    => 0,
                'total'       => $v['total'],
                'costo_total' => $v['costo_total'],
                'estado'      => 'completada',
                'fecha'       => Carbon::now()->subMinutes(rand(10, 90)),
            ]);
        }

        $this->command->line('  ✓ Turno abierto + 3 ventas del día creadas');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // COMPRAS PENDIENTES
    // ─────────────────────────────────────────────────────────────────────────
    private function seedCompras(): void
    {
        $adminId     = DB::table('usuarios')->where('email', 'admin@cafeteria.upds')->value('id');
        $proveedores = DB::table('proveedores')->pluck('id', 'nombre_empresa');
        $productos   = DB::table('productos')->pluck('id', 'nombre');

        $compras = [
            [
                'codigo'       => 'OC-' . date('Ymd') . '-001',
                'proveedor_id' => $proveedores['Distribuidora Café Bolivia'] ?? null,
                'usuario_id'   => $adminId,
                'estado'       => 'pendiente',
                'subtotal'     => 425.00,
                'total'        => 425.00,
                'nota'         => 'Reposición urgente de café',
                'items'        => [
                    ['producto' => 'Café en grano', 'cantidad' => 50, 'costo' => 8.50],
                ],
            ],
            [
                'codigo'       => 'OC-' . date('Ymd') . '-002',
                'proveedor_id' => $proveedores['Lácteos del Sur'] ?? null,
                'usuario_id'   => $adminId,
                'estado'       => 'pendiente',
                'subtotal'     => 126.00,
                'total'        => 126.00,
                'nota'         => 'Compra semanal de leche',
                'items'        => [
                    ['producto' => 'Leche entera', 'cantidad' => 30, 'costo' => 4.20],
                ],
            ],
        ];

        foreach ($compras as $c) {
            $items = $c['items'];
            unset($c['items']);

            $existe = DB::table('compras')->where('codigo', $c['codigo'])->exists();
            if (!$existe && $c['proveedor_id']) {
                $compraId = DB::table('compras')->insertGetId($c);
                foreach ($items as $item) {
                    if (isset($productos[$item['producto']])) {
                        DB::table('detalle_compra')->insert([
                            'compra_id'          => $compraId,
                            'producto_id'        => $productos[$item['producto']],
                            'cantidad_ordenada'  => $item['cantidad'],
                            'costo_unitario'     => $item['costo'],
                            'subtotal'           => $item['cantidad'] * $item['costo'],
                            'cantidad_recibida'  => 0,
                        ]);
                    }
                }
            }
        }

        $this->command->line('  ✓ Compras pendientes creadas (2)');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GASTOS OPERATIVOS DE HOY
    // ─────────────────────────────────────────────────────────────────────────
    private function seedGastos(): void
    {
        $adminId = DB::table('usuarios')->where('email', 'admin@cafeteria.upds')->value('id');
        $turnoId = DB::table('turnos')->where('estado', 'abierto')->value('id');

        $gastos = [
            [
                'turno_id'    => $turnoId,
                'categoria'   => 'servicios',
                'descripcion' => 'Pago de electricidad — cuota diaria',
                'monto'       => 35.00,
                'usuario_id'  => $adminId,
                'fecha'       => Carbon::today(),
            ],
            [
                'turno_id'    => $turnoId,
                'categoria'   => 'insumos',
                'descripcion' => 'Servilletas y vasos desechables',
                'monto'       => 28.50,
                'usuario_id'  => $adminId,
                'fecha'       => Carbon::today(),
            ],
        ];

        foreach ($gastos as $g) {
            DB::table('gastos_operativos')->insertOrIgnore($g);
        }

        $this->command->line('  ✓ Gastos operativos del día creados (2)');
    }
}
