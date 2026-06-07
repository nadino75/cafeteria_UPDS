<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\CompraController;
use App\Http\Controllers\Api\ContabilidadController;
use App\Http\Controllers\Api\GastoOperativoController;
use App\Http\Controllers\Api\InventarioController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\PantallaController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\ProveedorController;
use App\Http\Controllers\Api\ReporteController;
use App\Http\Controllers\Api\TurnoController;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\VentaController;
use Illuminate\Support\Facades\Route;

// ==========================================
// Rutas públicas (sin autenticación)
// ==========================================
Route::prefix('auth')->group(function () {
    Route::post('login',   [AuthController::class, 'login'])->middleware('throttle:10,1');
});

Route::get('menus-publicos', [MenuController::class, 'publicos']);
Route::get('pantalla/contenido', [PantallaController::class, 'publicos']);

// ==========================================
// Rutas protegidas (requieren JWT + permisos)
// ==========================================
Route::middleware('jwt.auth')->group(function () {

    // Auth (autogestión)
    Route::prefix('auth')->group(function () {
        Route::get('me',      [AuthController::class, 'me']);
        Route::post('refresh',[AuthController::class, 'refresh']);
        Route::post('logout', [AuthController::class, 'logout']);
    });

    // ─────────────── Categorías (inventario) ───────────────
    Route::get('categorias',             [CategoriaController::class, 'index'])->middleware('permisos:inventario,leer');
    Route::post('categorias',            [CategoriaController::class, 'store'])->middleware('permisos:inventario,crear');
    Route::get('categorias/{categoria}', [CategoriaController::class, 'show'])->middleware('permisos:inventario,leer');
    Route::put('categorias/{categoria}', [CategoriaController::class, 'update'])->middleware('permisos:inventario,editar');
    Route::patch('categorias/{categoria}',[CategoriaController::class, 'update'])->middleware('permisos:inventario,editar');
    Route::delete('categorias/{categoria}',[CategoriaController::class, 'destroy'])->middleware('permisos:inventario,eliminar');

    // ─────────────── Clientes ───────────────
    Route::get('clientes',             [ClienteController::class, 'index'])->middleware('permisos:clientes,leer');
    Route::post('clientes',            [ClienteController::class, 'store'])->middleware('permisos:clientes,crear');
    Route::get('clientes/{cliente}',   [ClienteController::class, 'show'])->middleware('permisos:clientes,leer');
    Route::put('clientes/{cliente}',   [ClienteController::class, 'update'])->middleware('permisos:clientes,editar');
    Route::patch('clientes/{cliente}', [ClienteController::class, 'update'])->middleware('permisos:clientes,editar');
    Route::post('clientes/{cliente}/canjear-puntos', [ClienteController::class, 'canjearPuntos'])->middleware('permisos:clientes,editar');

    // ─────────────── Proveedores ───────────────
    Route::get('proveedores',             [ProveedorController::class, 'index'])->middleware('permisos:proveedores,leer');
    Route::post('proveedores',            [ProveedorController::class, 'store'])->middleware('permisos:proveedores,crear');
    Route::get('proveedores/{proveedor}', [ProveedorController::class, 'show'])->middleware('permisos:proveedores,leer');
    Route::put('proveedores/{proveedor}', [ProveedorController::class, 'update'])->middleware('permisos:proveedores,editar');
    Route::patch('proveedores/{proveedor}',[ProveedorController::class, 'update'])->middleware('permisos:proveedores,editar');
    Route::delete('proveedores/{proveedor}',[ProveedorController::class, 'destroy'])->middleware('permisos:proveedores,eliminar');

    // ─────────────── Productos (inventario) ───────────────
    Route::get('productos',             [ProductoController::class, 'index'])->middleware('permisos:inventario,leer');
    Route::post('productos',            [ProductoController::class, 'store'])->middleware('permisos:inventario,crear');
    Route::get('productos/{producto}',  [ProductoController::class, 'show'])->middleware('permisos:inventario,leer');
    Route::put('productos/{producto}',  [ProductoController::class, 'update'])->middleware('permisos:inventario,editar');
    Route::patch('productos/{producto}',[ProductoController::class, 'update'])->middleware('permisos:inventario,editar');
    Route::delete('productos/{producto}',[ProductoController::class, 'destroy'])->middleware('permisos:inventario,eliminar');

    // ─────────────── Menús ───────────────
    Route::get('menus',             [MenuController::class, 'index'])->middleware('permisos:menus,leer');
    Route::post('menus',            [MenuController::class, 'store'])->middleware('permisos:menus,crear');
    Route::get('menus/{menu}',      [MenuController::class, 'show'])->middleware('permisos:menus,leer');
    Route::put('menus/{menu}',      [MenuController::class, 'update'])->middleware('permisos:menus,editar');
    Route::patch('menus/{menu}',    [MenuController::class, 'update'])->middleware('permisos:menus,editar');
    Route::delete('menus/{menu}',   [MenuController::class, 'destroy'])->middleware('permisos:menus,eliminar');

    // ─────────────── Usuarios ───────────────
    Route::get('usuarios',             [UsuarioController::class, 'index'])->middleware('permisos:usuarios,leer');
    Route::post('usuarios',            [UsuarioController::class, 'store'])->middleware('permisos:usuarios,crear');
    Route::get('usuarios/{usuario}',   [UsuarioController::class, 'show'])->middleware('permisos:usuarios,leer');
    Route::put('usuarios/{usuario}',   [UsuarioController::class, 'update'])->middleware('permisos:usuarios,editar');
    Route::patch('usuarios/{usuario}', [UsuarioController::class, 'update'])->middleware('permisos:usuarios,editar');
    Route::delete('usuarios/{usuario}',[UsuarioController::class, 'destroy'])->middleware('permisos:usuarios,eliminar');

    // ─────────────── Inventario ───────────────
    Route::prefix('inventario')->group(function () {
        Route::get('lotes',             [InventarioController::class, 'lotes'])->middleware('permisos:inventario,leer');
        Route::get('movimientos',       [InventarioController::class, 'movimientos'])->middleware('permisos:inventario,leer');
        Route::get('stock-bajo',        [InventarioController::class, 'stockBajo'])->middleware('permisos:inventario,leer');
        Route::get('vencimientos',      [InventarioController::class, 'alertasVencimiento'])->middleware('permisos:inventario,leer');
        Route::post('ajuste',           [InventarioController::class, 'ajustarStock'])->middleware('permisos:inventario,crear');
        Route::get('alertas',           [InventarioController::class, 'alertas'])->middleware('permisos:inventario,leer');
        Route::get('alertas-dashboard', [InventarioController::class, 'alertasDashboard'])->middleware('permisos:inventario,leer');
    });

    // ─────────────── Turnos ───────────────
    Route::prefix('turnos')->group(function () {
        Route::get('/',                       [TurnoController::class, 'index'])->middleware('permisos:turnos,leer');
        Route::get('activo',                  [TurnoController::class, 'miTurnoActivo'])->middleware('permisos:turnos,leer');
        Route::get('pendientes-validar',      [TurnoController::class, 'pendientesValidar'])->middleware('permisos:turnos,leer');
        Route::get('{turno}',                 [TurnoController::class, 'show'])->middleware('permisos:turnos,leer');
        Route::get('{turno}/resumen-cierre',  [TurnoController::class, 'resumenCierre'])->middleware('permisos:turnos,leer');
        Route::post('abrir',                  [TurnoController::class, 'abrir'])->middleware('permisos:turnos,crear');
        Route::post('{turno}/cerrar',         [TurnoController::class, 'cerrar'])->middleware('permisos:turnos,aprobar');
    });
    Route::post('cortes/{corte}/validar-entrega', [TurnoController::class, 'validarEntrega'])->middleware('permisos:turnos,aprobar');

    // ─────────────── Ventas ───────────────
    Route::prefix('ventas')->group(function () {
        Route::get('/',             [VentaController::class, 'index'])->middleware('permisos:ventas,leer');
        Route::get('{venta}',       [VentaController::class, 'show'])->middleware('permisos:ventas,leer');
        Route::post('/',            [VentaController::class, 'store'])->middleware('permisos:ventas,crear');
        Route::patch('{venta}/cancelar', [VentaController::class, 'cancelar'])->middleware('permisos:ventas,editar');
    });

    // ─────────────── Compras ───────────────
    Route::prefix('compras')->group(function () {
        Route::get('/',                      [CompraController::class, 'index'])->middleware('permisos:compras,leer');
        Route::get('{compra}',               [CompraController::class, 'show'])->middleware('permisos:compras,leer');
        Route::post('/',                     [CompraController::class, 'store'])->middleware('permisos:compras,crear');
        Route::post('{compra}/recibir',      [CompraController::class, 'recibirCompra'])->middleware('permisos:compras,aprobar');
    });

    // ─────────────── Gastos operativos ───────────────
    Route::get('gastos',             [GastoOperativoController::class, 'index'])->middleware('permisos:gastos,leer');
    Route::post('gastos',            [GastoOperativoController::class, 'store'])->middleware('permisos:gastos,crear');
    Route::get('gastos/{gasto}',     [GastoOperativoController::class, 'show'])->middleware('permisos:gastos,leer');
    Route::put('gastos/{gasto}',     [GastoOperativoController::class, 'update'])->middleware('permisos:gastos,editar');
    Route::patch('gastos/{gasto}',   [GastoOperativoController::class, 'update'])->middleware('permisos:gastos,editar');
    Route::delete('gastos/{gasto}',  [GastoOperativoController::class, 'destroy'])->middleware('permisos:gastos,eliminar');

    // ─────────────── Reportes ───────────────
    Route::prefix('reportes')->group(function () {
        Route::get('ventas-diarias',       [ReporteController::class, 'ventasDiarias'])->middleware('permisos:reportes,leer');
        Route::get('productos-vendidos',   [ReporteController::class, 'productosMasVendidos'])->middleware('permisos:reportes,leer');
        Route::get('balance-diario',       [ReporteController::class, 'balanceDiario'])->middleware('permisos:reportes,leer');
        Route::get('resumen-mensual',      [ReporteController::class, 'resumenMensual'])->middleware('permisos:reportes,leer');
        Route::get('cierres-diarios',      [ReporteController::class, 'cierresDiarios'])->middleware('permisos:reportes,leer');
    });

    // ─────────────── Contabilidad ───────────────
    Route::prefix('contabilidad')->group(function () {
        Route::get('tendencia',            [ContabilidadController::class, 'tendencia'])->middleware('permisos:contabilidad,leer');
        Route::get('comparativa',          [ContabilidadController::class, 'comparativa'])->middleware('permisos:contabilidad,leer');
        Route::get('pyg',                  [ContabilidadController::class, 'pyg'])->middleware('permisos:contabilidad,leer');
        Route::get('balance-general',      [ContabilidadController::class, 'balanceGeneral'])->middleware('permisos:contabilidad,leer');
        Route::get('asientos',             [ContabilidadController::class, 'asientos'])->middleware('permisos:contabilidad,leer');
        Route::get('cierres-pendientes',   [ContabilidadController::class, 'cierresPendientes'])->middleware('permisos:contabilidad,leer');
        Route::post('ejecutar-cierre',     [ContabilidadController::class, 'ejecutarCierre'])->middleware('permisos:contabilidad,aprobar');
    });

    // ─────────────── Pantalla ───────────────
    Route::get('pantalla', [PantallaController::class, 'index'])->middleware('permisos:pantalla,leer');
    Route::post('pantalla', [PantallaController::class, 'store'])->middleware('permisos:pantalla,crear');
    Route::put('pantalla/{pantalla}', [PantallaController::class, 'update'])->middleware('permisos:pantalla,editar');
    Route::patch('pantalla/{pantalla}/toggle', [PantallaController::class, 'toggle'])->middleware('permisos:pantalla,editar');
    Route::post('pantalla/reordenar', [PantallaController::class, 'reordenar'])->middleware('permisos:pantalla,editar');
    Route::delete('pantalla/{pantalla}', [PantallaController::class, 'destroy'])->middleware('permisos:pantalla,eliminar');
});
