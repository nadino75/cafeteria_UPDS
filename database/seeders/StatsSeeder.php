<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

/**
 * Genera datos estadísticos completos para dashboards y reportes.
 * Incluye 30 días de ventas, cierres diarios, balances, lotes con alertas.
 *
 * Ejecutar: php artisan db:seed --class=StatsSeeder
 * Reset: php artisan db:seed --class=StatsSeeder  (idempotente con truncate)
 */
class StatsSeeder extends Seeder
{
    private array $productos = [];
    private array $menus     = [];
    private array $clientes  = [];
    private array $users     = [];
    private array $turnos    = [];

    public function run(): void
    {
        $this->command->info('Generando datos estadísticos...');
        $this->seedProductos();
        $this->seedLotes();
        $this->seedTurnosYVentas();       // turnos con cierre_diario_id = null
        $this->seedCierresYBalances();    // crear cierres, luego linkear turnos
        $this->seedReporteMensual();
        $this->command->info('✓ Datos estadísticos listos.');
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PRODUCTOS — stock crítico, bajo, normal y sin stock
    // ═══════════════════════════════════════════════════════════════════════════
    private function seedProductos(): void
    {
        $cats = DB::table('categorias')->pluck('id', 'nombre');
        $existentes = DB::table('productos')->pluck('id', 'codigo');

        $nuevos = [
            // Productos de venta directa
            ['nombre' => 'Galleta de avena',         'codigo' => 'ALI-001', 'categoria_id' => $cats['Alimentos'] ?? null,            'precio_venta' => 3.50,  'costo_unitario' => 1.20, 'stock_actual' => 0,   'stock_minimo' => 20,  'unidad_medida' => 'unidad', 'requiere_lote' => false],
            ['nombre' => 'Empanada de queso',         'codigo' => 'ALI-002', 'categoria_id' => $cats['Alimentos'] ?? null,            'precio_venta' => 7.00,  'costo_unitario' => 3.00, 'stock_actual' => 2,   'stock_minimo' => 15,  'unidad_medida' => 'unidad', 'requiere_lote' => false],
            ['nombre' => 'Pastel de chocolate',       'codigo' => 'ALI-003', 'categoria_id' => $cats['Repostería'] ?? null,           'precio_venta' => 12.00, 'costo_unitario' => 5.50, 'stock_actual' => 1,   'stock_minimo' => 10,  'unidad_medida' => 'unidad', 'requiere_lote' => false],
            ['nombre' => 'Sandwich mixto',            'codigo' => 'ALI-004', 'categoria_id' => $cats['Alimentos'] ?? null,            'precio_venta' => 15.00, 'costo_unitario' => 6.00, 'stock_actual' => 3,   'stock_minimo' => 10,  'unidad_medida' => 'unidad', 'requiere_lote' => false],
            ['nombre' => 'Jugo natural de naranja',   'codigo' => 'BEB-003', 'categoria_id' => $cats['Bebidas Frías'] ?? null,        'precio_venta' => 10.00, 'costo_unitario' => 3.00, 'stock_actual' => 5,   'stock_minimo' => 8,   'unidad_medida' => 'lt',    'requiere_lote' => false],
            ['nombre' => 'Te de coca',                'codigo' => 'BEB-004', 'categoria_id' => $cats['Bebidas Calientes'] ?? null,    'precio_venta' => 8.00,  'costo_unitario' => 1.50, 'stock_actual' => 50,  'stock_minimo' => 10,  'unidad_medida' => 'unidad', 'requiere_lote' => false],
            // Más insumos
            ['nombre' => 'Mantequilla',               'codigo' => 'INS-006', 'categoria_id' => $cats['Insumos / Materia Prima'] ?? null, 'precio_venta' => 0,   'costo_unitario' => 15.00,'stock_actual' => 1,   'stock_minimo' => 5,   'unidad_medida' => 'kg',    'requiere_lote' => true],
            ['nombre' => 'Huevos',                    'codigo' => 'INS-007', 'categoria_id' => $cats['Insumos / Materia Prima'] ?? null, 'precio_venta' => 0,   'costo_unitario' => 0.80, 'stock_actual' => 6,   'stock_minimo' => 24,  'unidad_medida' => 'unidad', 'requiere_lote' => false],
            ['nombre' => 'Queso crema',               'codigo' => 'INS-008', 'categoria_id' => $cats['Insumos / Materia Prima'] ?? null, 'precio_venta' => 0,   'costo_unitario' => 22.00,'stock_actual' => 2,   'stock_minimo' => 3,   'unidad_medida' => 'kg',    'requiere_lote' => true],
            ['nombre' => 'Pan de molde',              'codigo' => 'INS-009', 'categoria_id' => $cats['Insumos / Materia Prima'] ?? null, 'precio_venta' => 0,   'costo_unitario' => 5.00, 'stock_actual' => 4,   'stock_minimo' => 10,  'unidad_medida' => 'unidad', 'requiere_lote' => false],
            ['nombre' => 'Canela en polvo',           'codigo' => 'INS-010', 'categoria_id' => $cats['Insumos / Materia Prima'] ?? null, 'precio_venta' => 0,   'costo_unitario' => 18.00,'stock_actual' => 0,   'stock_minimo' => 2,   'unidad_medida' => 'kg',    'requiere_lote' => false],
            ['nombre' => 'Café molido premium',       'codigo' => 'INS-011', 'categoria_id' => $cats['Insumos / Materia Prima'] ?? null, 'precio_venta' => 0,   'costo_unitario' => 25.00,'stock_actual' => 0,   'stock_minimo' => 8,   'unidad_medida' => 'kg',    'requiere_lote' => true],
        ];

        foreach ($nuevos as $p) {
            if (!isset($existentes[$p['codigo']]) && $p['categoria_id']) {
                DB::table('productos')->insertOrIgnore($p);
            }
        }

        $this->productos = DB::table('productos')->pluck('id', 'codigo')->toArray();
        $this->command->line('  ✓ Productos expandidos (stock crítico, bajo, sin stock)');
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // LOTES — algunos vencidos, por vencer, y vigentes
    // ═══════════════════════════════════════════════════════════════════════════
    private function seedLotes(): void
    {
        $adminId = DB::table('usuarios')->where('email', 'admin@cafeteria.upds')?->value('id') ?? 1;
        $prods   = DB::table('productos')->pluck('id', 'codigo');

        $lotes = [
            // Vencidos
            ['codigo' => 'INS-006', 'cantidad' => 1, 'costo' => 15.00, 'dias_entrada' => -20, 'dias_vencimiento' => -2, 'lote' => 'MANT-ENE'],
            ['codigo' => 'INS-001', 'cantidad' => 2, 'costo' => 8.50,  'dias_entrada' => -35, 'dias_vencimiento' => -1, 'lote' => 'CAFE-DIC'],
            // Vencen HOY
            ['codigo' => 'INS-008', 'cantidad' => 1, 'costo' => 22.00, 'dias_entrada' => -7,  'dias_vencimiento' => 0,  'lote' => 'QCR-2024-01'],
            ['codigo' => 'INS-002', 'cantidad' => 2, 'costo' => 4.20,  'dias_entrada' => -4,  'dias_vencimiento' => 0,  'lote' => 'LECHE-JUN'],
            // Vencen en 1-2 días
            ['codigo' => 'INS-001', 'cantidad' => 1, 'costo' => 8.50,  'dias_entrada' => -28, 'dias_vencimiento' => 1,  'lote' => 'CAFE-2024-002'],
            ['codigo' => 'INS-002', 'cantidad' => 3, 'costo' => 4.20,  'dias_entrada' => -3,  'dias_vencimiento' => 2,  'lote' => 'LECHE-JUL'],
            // Vencen en 5-7 días
            ['codigo' => 'INS-006', 'cantidad' => 1, 'costo' => 15.00, 'dias_entrada' => -15, 'dias_vencimiento' => 5,  'lote' => 'MANT-FEB'],
            ['codigo' => 'INS-008', 'cantidad' => 1, 'costo' => 22.00, 'dias_entrada' => -5,  'dias_vencimiento' => 7,  'lote' => 'QCR-2024-02'],
            // Vigentes
            ['codigo' => 'INS-001', 'cantidad' => 3, 'costo' => 8.50,  'dias_entrada' => -2,  'dias_vencimiento' => 60, 'lote' => 'CAFE-NVO'],
            ['codigo' => 'INS-002', 'cantidad' => 5, 'costo' => 4.00,  'dias_entrada' => -1,  'dias_vencimiento' => 14, 'lote' => 'LECHE-AGO'],
            ['codigo' => 'INS-005', 'cantidad' => 10,'costo' => 12.00, 'dias_entrada' => -10, 'dias_vencimiento' => 180,'lote' => 'CHOC-2024'],
            ['codigo' => 'BEB-001', 'cantidad' => 24,'costo' => 2.50,  'dias_entrada' => -5,  'dias_vencimiento' => 365,'lote' => 'AGUA-2024-089'],
            ['codigo' => 'BEB-002', 'cantidad' => 12,'costo' => 4.50,  'dias_entrada' => -3,  'dias_vencimiento' => 180,'lote' => 'REFR-2024'],
        ];

        $now = Carbon::now();
        foreach ($lotes as $l) {
            $prodId = $prods[$l['codigo']] ?? null;
            if (!$prodId) continue;

            $numLote = $l['lote'] . '-' . $now->format('m');
            $existe = DB::table('lotes_inventario')
                ->where('producto_id', $prodId)
                ->where('numero_lote', $numLote)
                ->exists();
            if ($existe) continue;

            $fechaEntrada = (clone $now)->addDays($l['dias_entrada']);
            $fechaVenc = $l['dias_vencimiento'] !== null ? (clone $now)->addDays($l['dias_vencimiento']) : null;

            DB::table('lotes_inventario')->insert([
                'producto_id'          => $prodId,
                'cantidad_inicial'     => $l['cantidad'],
                'cantidad_disponible'  => $l['cantidad'],
                'costo_unitario'       => $l['costo'],
                'fecha_entrada'        => $fechaEntrada,
                'fecha_vencimiento'    => $fechaVenc,
                'numero_lote'          => $numLote,
                'estado'               => 'disponible',
            ]);

            DB::table('movimientos_inventario')->insert([
                'producto_id'     => $prodId,
                'tipo'            => 'entrada',
                'cantidad'        => $l['cantidad'],
                'costo_unitario'  => $l['costo'],
                'motivo'          => 'Stock inicial de demostración',
                'usuario_id'      => $adminId,
                'fecha'           => $fechaEntrada,
                'referencia_tipo' => 'ajuste_manual',
                'referencia_id'   => null,
            ]);
        }

        // Actualizar stock_actual en productos sumando lotes disponibles
        $this->actualizarStockDesdeLotes();

        $this->command->line('  ✓ Lotes expandidos (vencidos, por vencer hoy/mañana, vigentes)');
    }

    private function actualizarStockDesdeLotes(): void
    {
        $lotes = DB::table('lotes_inventario')
            ->select('producto_id', DB::raw('SUM(cantidad_disponible) as total'))
            ->where('estado', 'disponible')
            ->groupBy('producto_id')
            ->get();

        foreach ($lotes as $l) {
            DB::table('productos')->where('id', $l->producto_id)->update(['stock_actual' => $l->total]);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // TURNOS + VENTAS — 30 días de historia
    // ═══════════════════════════════════════════════════════════════════════════
    private function seedTurnosYVentas(): void
    {
        $this->users    = DB::table('usuarios')->pluck('id', 'email')->toArray();
        $cajeroId       = $this->users['cajero@cafeteria.upds'] ?? null;
        $adminId        = $this->users['admin@cafeteria.upds'] ?? 1;
        $gerenteId      = $this->users['gerente@cafeteria.upds'] ?? $adminId;
        if (!$cajeroId) { $this->command->warn('  ⚠ Cajero no encontrado, ejecuta DemoSeeder primero'); return; }

        $this->productos = DB::table('productos')->pluck('id', 'codigo')->toArray();
        $this->menus     = DB::table('menus')->pluck('id', 'nombre')->toArray();
        $this->clientes  = DB::table('clientes')->pluck('id', 'nombre')->toArray();

        $metodosPago = ['efectivo', 'efectivo', 'efectivo', 'tarjeta', 'tarjeta', 'transferencia'];
        $nombresMenus = ['Café americano', 'Café con leche', 'Chocolate caliente', 'Croissant de mantequilla', 'Combo mañana'];
        $nombresClientes = array_keys($this->clientes);

        $now = Carbon::now();

        // Crear turnos y ventas para cada día de los últimos 30 días
        for ($dia = 30; $dia >= 0; $dia--) {
            $fecha = (clone $now)->subDays($dia);
            $esHoy = $dia === 0;

            // Saltar fines de semana para simular menor actividad
            if ($fecha->isSaturday() || $fecha->isSunday()) {
                if (!$esHoy && rand(0, 2) > 0) continue; // 33% actividad finde
            }

            // Crear 1-2 turnos por día
            $numTurnos = $esHoy ? 1 : rand(1, 2);
            for ($t = 0; $t < $numTurnos; $t++) {
                $horaApertura = (clone $fecha)->setTime(6 + rand(0, 2), rand(0, 59));
                $horaCierre   = $esHoy ? null : (clone $horaApertura)->addHours(6 + rand(0, 4));
                $usuarioTurno  = rand(0, 3) > 0 ? $cajeroId : $gerenteId;

                $turnoId = DB::table('turnos')->insertGetId([
                    'codigo'              => 'T-' . $fecha->format('Ymd') . '-' . str_pad((string)($t + 1), 2, '0', STR_PAD_LEFT),
                    'usuario_apertura'    => $usuarioTurno,
                    'usuario_cierre'      => $esHoy ? null : $usuarioTurno,
                    'fecha_apertura'      => $horaApertura,
                    'fecha_cierre'        => $horaCierre,
                    'caja_inicial'        => 200.00,
                    'estado'              => $esHoy ? 'abierto' : 'cerrado',
                    'cierre_diario_id'    => null, // se actualiza después de crear cierres
                ]);

                if ($esHoy) {
                    // Solo unas pocas ventas para hoy
                    $numVentas = rand(2, 4);
                } else {
                    $numVentas = $fecha->isWeekend() ? rand(3, 8) : rand(5, 15);
                }

                for ($v = 0; $v < $numVentas; $v++) {
                    $this->crearVenta($turnoId, $usuarioTurno, $horaApertura, $horaCierre, $metodosPago, $nombresMenus, $nombresClientes, $esHoy);
                }
            }
        }

        // Crear HOY un turno abierto extra si demo ya tiene uno
        if (!DB::table('turnos')->where('fecha_apertura', '>=', $now->copy()->subHours(12))->where('estado', 'abierto')->exists()) {
            $horaApertura = (clone $now)->subHours(2)->setMinute(rand(0, 59));
            DB::table('turnos')->insert([
                'codigo'           => 'T-' . $now->format('Ymd') . '-99',
                'usuario_apertura' => $cajeroId,
                'fecha_apertura'   => $horaApertura,
                'caja_inicial'     => 200.00,
                'estado'           => 'abierto',
            ]);
        }

        $this->command->line('  ✓ Turnos y ventas creados (30 días de historia)');
    }

    private function crearVenta(int $turnoId, int $usuarioId, Carbon $horaApertura, ?Carbon $horaCierre, array $metodosPago, array $nombresMenus, array $nombresClientes, bool $esHoy): void
    {
        // Aleatorizar hora de venta dentro del turno
        if ($esHoy) {
            $horaVenta = Carbon::now()->subMinutes(rand(5, 180));
        } elseif ($horaCierre) {
            $horaVenta = (clone $horaApertura)->addMinutes(rand(10, max(10, (int)$horaApertura->diffInMinutes($horaCierre) - 10)));
        } else {
            $horaVenta = (clone $horaApertura)->addMinutes(rand(10, 240));
        }

        // Seleccionar items (1-3 por venta)
        $numItems = rand(1, 3);
        $items = [];
        $total = 0;
        $costoTotal = 0;

        for ($i = 0; $i < $numItems; $i++) {
            $esMenu = rand(0, 1) === 0 && !empty($this->menus);
            if ($esMenu) {
                $menuNombre = $nombresMenus[array_rand($nombresMenus)];
                $menuId = $this->menus[$menuNombre] ?? null;
                if (!$menuId) continue;

                $menu = DB::table('menus')->where('id', $menuId)->first();
                if (!$menu) continue;

                $cantidad = rand(1, 2);
                $precio = (float)$menu->precio_venta;
                $items[] = [
                    'tipo_item'   => 'menu',
                    'menu_id'     => $menuId,
                    'producto_id' => null,
                    'cantidad'    => $cantidad,
                    'precio_unitario' => $precio,
                    'descuento_item'  => 0,
                    'subtotal'    => $precio * $cantidad,
                ];
                $total += $precio * $cantidad;
                $costoTotal += ($precio * $cantidad) * 0.4; // Estimar 40% de margen
            } else {
                $prodKeys = array_keys($this->productos);
                $codigo = $prodKeys[array_rand($prodKeys)];
                $prodId = $this->productos[$codigo];
                $prod = DB::table('productos')->where('id', $prodId)->first();
                if (!$prod || (float)$prod->precio_venta <= 0) continue;

                $cantidad = rand(1, 3);
                $precio = (float)$prod->precio_venta;
                $items[] = [
                    'tipo_item'   => 'producto',
                    'menu_id'     => null,
                    'producto_id' => $prodId,
                    'cantidad'    => $cantidad,
                    'precio_unitario' => $precio,
                    'descuento_item'  => 0,
                    'subtotal'    => $precio * $cantidad,
                ];
                $total += $precio * $cantidad;
                $costoTotal += (float)$prod->costo_unitario * $cantidad;
            }
        }

        if (empty($items)) return;

        $metodo     = $metodosPago[array_rand($metodosPago)];
        $clienteNom = $nombresClientes[array_rand($nombresClientes)];
        $clienteId  = $this->clientes[$clienteNom] ?? null;

        $descuentoGlobal = rand(0, 5) === 0 ? round($total * 0.1, 2) : 0;
        $impuesto = 0;
        $totalFinal = $total - $descuentoGlobal + $impuesto;

        $ventaId = DB::table('ventas')->insertGetId([
            'turno_id'    => $turnoId,
            'usuario_id'  => $usuarioId,
            'cliente_id'  => $clienteId,
            'fecha'       => $horaVenta,
            'subtotal'    => $total,
            'descuento'   => $descuentoGlobal,
            'impuesto'    => $impuesto,
            'total'       => $totalFinal,
            'costo_total' => round($costoTotal, 2),
            'metodo_pago' => $metodo,
            'estado'      => 'completada',
            'nota'        => null,
        ]);

        foreach ($items as $item) {
            DB::table('detalle_venta')->insert(array_merge($item, ['venta_id' => $ventaId, 'costo_fifo' => 0]));
        }

        // Puntos cliente
        if ($clienteId) {
            DB::table('clientes')->where('id', $clienteId)->increment('puntos_acumulados', (int)$totalFinal);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // CIERRES DIARIOS + BALANCE DIARIO
    // ═══════════════════════════════════════════════════════════════════════════
    private function seedCierresYBalances(): void
    {
        $adminId     = $this->users['admin@cafeteria.upds'] ?? DB::table('usuarios')->value('id');
        $contadorId  = $this->users['contador@cafeteria.upds'] ?? $adminId;
        $now         = Carbon::now();

        // Crear gastos adicionales para días pasados
        $categoriasGasto = ['servicios', 'mantenimiento', 'insumos', 'otros'];

        for ($dia = 30; $dia >= 0; $dia--) {
            $fecha = (clone $now)->subDays($dia);
            $fechaStr = $fecha->toDateString();

            // Cierre diario ya existe?
            if (DB::table('cierres_diarios')->where('fecha', $fechaStr)->exists()) continue;

            // Obtener ventas del día
            $ventas = DB::table('ventas')
                ->whereDate('fecha', $fechaStr)
                ->where('estado', 'completada')
                ->get();

            if ($ventas->isEmpty() && $dia > 0) continue;

            $totalVentas = 0;
            $totalEfectivo = 0;
            $totalTarjeta = 0;
            $totalTransferencia = 0;
            $totalDescuentos = 0;
            $totalImpuestos = 0;

            foreach ($ventas as $v) {
                $totalVentas += (float)$v->total;
                $totalDescuentos += (float)$v->descuento;
                $totalImpuestos += (float)$v->impuesto;
                match ($v->metodo_pago) {
                    'efectivo'      => $totalEfectivo      += (float)$v->total,
                    'tarjeta'       => $totalTarjeta       += (float)$v->total,
                    'transferencia' => $totalTransferencia += (float)$v->total,
                    default         => $totalEfectivo      += (float)$v->total,
                };
            }

            // Gastos del día
            $totalGastos = (float) DB::table('gastos_operativos')->whereDate('fecha', $fechaStr)->sum('monto');

            // Agregar gastos simulados si no hay
            if ($totalGastos < 10) {
                $numGastos = rand(1, 2);
                for ($g = 0; $g < $numGastos; $g++) {
                    $cat = $categoriasGasto[array_rand($categoriasGasto)];
                    $montos = ['servicios' => rand(15, 60), 'mantenimiento' => rand(20, 80), 'insumos' => rand(10, 40), 'otros' => rand(5, 30)];
                    DB::table('gastos_operativos')->insert([
                        'turno_id'    => null,
                        'categoria'   => $cat,
                        'descripcion' => "Gasto {$cat} del {$fechaStr}",
                        'monto'       => $montos[$cat],
                        'usuario_id'  => $adminId,
                        'fecha'       => $fecha,
                    ]);
                    $totalGastos += $montos[$cat];
                }
            }

            // Compras del día
            $totalCompras = (float) DB::table('compras')->whereDate('fecha_orden', $fechaStr)->sum('total');

            $numTurnos = DB::table('turnos')->whereDate('fecha_apertura', $fechaStr)->count();

            $cierreId = DB::table('cierres_diarios')->insertGetId([
                'fecha'                   => $fechaStr,
                'usuario_id'              => $dia === 0 ? $contadorId : $adminId,
                'total_ventas'            => $totalVentas,
                'total_ventas_efectivo'   => $totalEfectivo,
                'total_ventas_tarjeta'    => $totalTarjeta,
                'total_ventas_transferencia' => $totalTransferencia,
                'total_descuentos'        => $totalDescuentos,
                'total_impuestos'         => $totalImpuestos,
                'total_compras'           => $totalCompras,
                'total_gastos_operativos' => $totalGastos,
                'num_ventas'              => $ventas->count(),
                'num_turnos'              => $numTurnos,
                'estado'                  => $dia === 0 ? 'borrador' : 'cerrado',
            ]);

            // Balance diario
            $cmv = (float) DB::table('ventas')->whereDate('fecha', $fechaStr)->where('estado', 'completada')->sum('costo_total');
            $ingresos = $totalVentas;
            $totalEgresos = $cmv + $totalGastos;

            DB::table('balance_diario')->insertOrIgnore([
                'fecha'                  => $fechaStr,
                'cierre_diario_id'       => $cierreId,
                'ingresos_ventas'        => $ingresos,
                'otros_ingresos'         => 0,
                'total_ingresos'         => $ingresos,
                'costo_mercancia_vendida'=> $cmv,
                'gastos_operativos'      => $totalGastos,
                'gastos_nomina'          => 0,
                'otros_gastos'           => 0,
                'total_egresos'          => $totalEgresos,
            ]);
        }

        // Vincular turnos con sus cierres
        foreach (DB::table('cierres_diarios')->get() as $c) {
            DB::table('turnos')->whereDate('fecha_apertura', $c->fecha)->update(['cierre_diario_id' => $c->id]);
        }

        $this->command->line('  ✓ Cierres diarios, balances y vinculación de turnos (30 días)');
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // REPORTE MENSUAL — mes actual y anterior
    // ═══════════════════════════════════════════════════════════════════════════
    private function seedReporteMensual(): void
    {
        $now = Carbon::now();
        foreach ([0, 1] as $offset) {
            $fecha = (clone $now)->subMonths($offset);
            $anio = (int)$fecha->format('Y');
            $mes = (int)$fecha->format('m');

            if (DB::table('reporte_mensual')->where('anio', $anio)->where('mes', $mes)->exists()) continue;

            $ventas = DB::table('ventas')
                ->whereYear('fecha', $anio)
                ->whereMonth('fecha', $mes)
                ->where('estado', 'completada');

            $totalVentas = (float)(clone $ventas)->sum('total');
            $totalCosto  = (float)(clone $ventas)->sum('costo_total');
            $numVentas   = (clone $ventas)->count();
            $gastos      = (float) DB::table('gastos_operativos')
                ->whereYear('fecha', $anio)->whereMonth('fecha', $mes)->sum('monto');

            $topProducto = DB::table('detalle_venta')
                ->join('ventas', 'detalle_venta.venta_id', '=', 'ventas.id')
                ->join('productos', 'detalle_venta.producto_id', '=', 'productos.id')
                ->whereYear('ventas.fecha', $anio)
                ->whereMonth('ventas.fecha', $mes)
                ->where('ventas.estado', 'completada')
                ->select('productos.nombre', DB::raw('SUM(detalle_venta.cantidad) as total'))
                ->groupBy('productos.nombre')
                ->orderByDesc('total')
                ->first();

            DB::table('reporte_mensual')->insert([
                'anio'                  => $anio,
                'mes'                   => $mes,
                'total_ventas'          => $totalVentas,
                'total_costo_mercancia' => $totalCosto,
                'total_gastos_operativos' => $gastos,
                'utilidad_bruta'        => $totalVentas - $totalCosto,
                'utilidad_neta'         => $totalVentas - $totalCosto - $gastos,
                'num_ventas'            => $numVentas,
                'ticket_promedio'       => $numVentas > 0 ? round($totalVentas / $numVentas, 2) : 0,
                'producto_mas_vendido'  => $topProducto?->nombre ?? '—',
                'generado_en'           => Carbon::now(),
            ]);
        }

        $this->command->line('  ✓ Reportes mensuales creados (mes actual y anterior)');
    }
}
