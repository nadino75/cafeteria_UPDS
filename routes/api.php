<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\CompraController;
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
Route::middleware('jwt.auth')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::get('me',      [AuthController::class, 'me']);
        Route::post('refresh',[AuthController::class, 'refresh']);
        Route::post('logout', [AuthController::class, 'logout']);
    });

    // Categorías — módulo: inventario/menus
    Route::apiResource('categorias', CategoriaController::class)
        ->middleware([
            'index,show:permisos:inventario,leer',
            'store:permisos:inventario,crear',
            'update:permisos:inventario,editar',
            'destroy:permisos:inventario,eliminar',
        ]);

    // Clientes — módulo: clientes
    Route::apiResource('clientes', ClienteController::class)->except(['destroy']);
    Route::post('clientes/{cliente}/canjear-puntos', [ClienteController::class, 'canjearPuntos'])
        ->middleware('permisos:clientes,editar');

    // Proveedores — módulo: compras
    Route::apiResource('proveedores', ProveedorController::class)
        ->middleware('permisos:compras,leer');

    // Productos — módulo: inventario
    Route::middleware('permisos:inventario,leer')->group(function () {
        Route::apiResource('productos', ProductoController::class);
    });

    // Menús — módulo: menus
    Route::middleware('permisos:menus,leer')->group(function () {
        Route::apiResource('menus', MenuController::class);
    });

    // Usuarios — módulo: usuarios (solo admin/gerente)
    Route::middleware('permisos:usuarios,leer')->group(function () {
        Route::apiResource('usuarios', UsuarioController::class);
    });

    // Inventario — módulo: inventario
    Route::prefix('inventario')->middleware('permisos:inventario,leer')->group(function () {
        Route::get('lotes',         [InventarioController::class, 'lotes']);
        Route::get('movimientos',   [InventarioController::class, 'movimientos']);
        Route::get('stock-bajo',    [InventarioController::class, 'stockBajo']);
        Route::get('vencimientos',  [InventarioController::class, 'alertasVencimiento']);
        Route::post('ajuste',       [InventarioController::class, 'ajustarStock'])
            ->middleware('permisos:inventario,editar');
    });

    // Turnos — módulo: turnos
    Route::prefix('turnos')->middleware('permisos:turnos,leer')->group(function () {
        Route::get('/',          [TurnoController::class, 'index']);
        Route::get('activo',     [TurnoController::class, 'miTurnoActivo']);
        Route::get('{turno}',    [TurnoController::class, 'show']);
        Route::post('abrir',     [TurnoController::class, 'abrir'])
            ->middleware('permisos:turnos,crear');
        Route::post('{turno}/cerrar', [TurnoController::class, 'cerrar'])
            ->middleware('permisos:turnos,aprobar');
    });

    // Ventas — módulo: ventas
    Route::prefix('ventas')->middleware('permisos:ventas,leer')->group(function () {
        Route::get('/',             [VentaController::class, 'index']);
        Route::get('{venta}',       [VentaController::class, 'show']);
        Route::post('/',            [VentaController::class, 'store'])
            ->middleware('permisos:ventas,crear');
        Route::patch('{venta}/cancelar', [VentaController::class, 'cancelar'])
            ->middleware('permisos:ventas,editar');
    });

    // Compras — módulo: compras
    Route::prefix('compras')->middleware('permisos:compras,leer')->group(function () {
        Route::get('/',                      [CompraController::class, 'index']);
        Route::get('{compra}',               [CompraController::class, 'show']);
        Route::post('/',                     [CompraController::class, 'store'])
            ->middleware('permisos:compras,crear');
        Route::post('{compra}/recibir',      [CompraController::class, 'recibirCompra'])
            ->middleware('permisos:compras,aprobar');
    });

    // Gastos operativos — módulo: gastos
    Route::middleware('permisos:gastos,leer')->group(function () {
        Route::apiResource('gastos', GastoOperativoController::class);
    });

    // Reportes — módulo: reportes
    Route::prefix('reportes')->middleware('permisos:reportes,leer')->group(function () {
        Route::get('ventas-diarias',       [ReporteController::class, 'ventasDiarias']);
        Route::get('productos-vendidos',   [ReporteController::class, 'productosMasVendidos']);
        Route::get('balance-diario',       [ReporteController::class, 'balanceDiario']);
        Route::get('resumen-mensual',      [ReporteController::class, 'resumenMensual']);
        Route::get('cierres-diarios',      [ReporteController::class, 'cierresDiarios']);
    });
});
