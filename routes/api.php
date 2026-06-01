<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\CompraController;
use App\Http\Controllers\Api\ContabilidadController;
use App\Http\Controllers\Api\GastoOperativoController;
use App\Http\Controllers\Api\InventarioController;
use App\Http\Controllers\Api\MenuController;
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
    Route::post('login',   [AuthController::class, 'login']);
});

// ==========================================
// Rutas protegidas (requieren JWT)
// ==========================================
// ⚠️  TEMPORAL: Middlewares de permisos comentados
// La tabla `permisos` no existe aún. Ver DEBUG_REPORT.md para plan de implementación.
// TODO: Restaurar middlewares cuando tabla permisos esté implementada.
Route::middleware('jwt.auth')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::get('me',      [AuthController::class, 'me']);
        Route::post('refresh',[AuthController::class, 'refresh']);
        Route::post('logout', [AuthController::class, 'logout']);
    });

    // Categorías
    Route::apiResource('categorias', CategoriaController::class);

    // Clientes
    Route::apiResource('clientes', ClienteController::class)->except(['destroy']);
    Route::post('clientes/{cliente}/canjear-puntos', [ClienteController::class, 'canjearPuntos']);

    // Proveedores
    Route::apiResource('proveedores', ProveedorController::class);

    // Productos
    Route::apiResource('productos', ProductoController::class);

    // Menús
    Route::apiResource('menus', MenuController::class);

    // Usuarios
    Route::apiResource('usuarios', UsuarioController::class);

    // Inventario
    Route::prefix('inventario')->group(function () {
        Route::get('lotes',         [InventarioController::class, 'lotes']);
        Route::get('movimientos',   [InventarioController::class, 'movimientos']);
        Route::get('stock-bajo',    [InventarioController::class, 'stockBajo']);
        Route::get('vencimientos',  [InventarioController::class, 'alertasVencimiento']);
        Route::post('ajuste',       [InventarioController::class, 'ajustarStock']);
        Route::get('alertas',       [InventarioController::class, 'alertas']);
        Route::get('alertas-dashboard', [InventarioController::class, 'alertasDashboard']);
    });

    // Turnos
    Route::prefix('turnos')->group(function () {
        Route::get('/',          [TurnoController::class, 'index']);
        Route::get('activo',     [TurnoController::class, 'miTurnoActivo']);
        Route::get('{turno}',    [TurnoController::class, 'show']);
        Route::post('abrir',     [TurnoController::class, 'abrir']);
        Route::post('{turno}/cerrar', [TurnoController::class, 'cerrar']);
    });

    // Ventas
    Route::prefix('ventas')->group(function () {
        Route::get('/',             [VentaController::class, 'index']);
        Route::get('{venta}',       [VentaController::class, 'show']);
        Route::post('/',            [VentaController::class, 'store']);
        Route::patch('{venta}/cancelar', [VentaController::class, 'cancelar']);
    });

    // Compras
    Route::prefix('compras')->group(function () {
        Route::get('/',                      [CompraController::class, 'index']);
        Route::get('{compra}',               [CompraController::class, 'show']);
        Route::post('/',                     [CompraController::class, 'store']);
        Route::post('{compra}/recibir',      [CompraController::class, 'recibirCompra']);
    });

    // Gastos operativos
    Route::apiResource('gastos', GastoOperativoController::class);

    // Reportes
    Route::prefix('reportes')->group(function () {
        Route::get('ventas-diarias',       [ReporteController::class, 'ventasDiarias']);
        Route::get('productos-vendidos',   [ReporteController::class, 'productosMasVendidos']);
        Route::get('balance-diario',       [ReporteController::class, 'balanceDiario']);
        Route::get('resumen-mensual',      [ReporteController::class, 'resumenMensual']);
        Route::get('cierres-diarios',      [ReporteController::class, 'cierresDiarios']);
    });

    // Contabilidad (doble entrada)
    Route::prefix('contabilidad')->group(function () {
        Route::get('tendencia',            [ContabilidadController::class, 'tendencia']);
        Route::get('comparativa',          [ContabilidadController::class, 'comparativa']);
        Route::get('pyg',                  [ContabilidadController::class, 'pyg']);
        Route::get('balance-general',      [ContabilidadController::class, 'balanceGeneral']);
        Route::get('asientos',             [ContabilidadController::class, 'asientos']);
        Route::get('cierres-pendientes',   [ContabilidadController::class, 'cierresPendientes']);
        Route::post('ejecutar-cierre',     [ContabilidadController::class, 'ejecutarCierre']);
    });
});
