<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Venta;
use App\Models\GastoOperativo;
use App\Models\Menu;

echo "=== VENTAS POR ESTADO ===\n";
$ventas = Venta::selectRaw("estado, COUNT(*) as n, ROUND(SUM(total),2) as total, ROUND(SUM(costo_total),2) as costo")->groupBy("estado")->get();
foreach ($ventas as $v) {
    echo "  {$v->estado}: {$v->n} ventas, total={$v->total}, costo={$v->costo}, utilidad=" . ($v->total - $v->costo) . "\n";
}
$resumen = Venta::selectRaw("ROUND(SUM(total),2) as ingresos, ROUND(SUM(costo_total),2) as costos")->where("estado", "completada")->first();
echo "\n  COMPLETADAS: ingresos={$resumen->ingresos}, costos={$resumen->costos}, utilidad_bruta=" . ($resumen->ingresos - $resumen->costos) . "\n";

echo "\n=== GASTOS OPERATIVOS ===\n";
echo "  Total: " . round(GastoOperativo::sum("monto"), 2) . "\n";

echo "\n=== UTILIDAD NETA ===\n";
$utilidad_neta = ($resumen->ingresos - $resumen->costos) - GastoOperativo::sum("monto");
echo "  Utilidad Neta: " . round($utilidad_neta, 2) . "\n";

echo "\n=== MENUS: PRECIO VS COSTO DE INGREDIENTES ===\n";
$menus = Menu::where("activo", true)->with("ingredientes.producto")->get();
foreach ($menus as $m) {
    $costo = 0;
    foreach ($m->ingredientes as $i) {
        $costo += $i->cantidad * ($i->producto->costo_unitario ?? 0);
    }
    $margen = $m->precio_venta - $costo;
    $porcentaje = $m->precio_venta > 0 ? round($costo / $m->precio_venta * 100, 1) : 0;
    echo "  {$m->nombre} ({$m->tipo}): precio={$m->precio_venta}, costo_ing={$costo}, margen={$margen} ({$porcentaje}%)\n";
}

echo "\n=== 3 ULTIMAS VENTAS CON DETALLE ===\n";
$recent = Venta::where("estado", "completada")->with("detalles")->orderBy("id", "desc")->take(3)->get();
foreach ($recent as $v) {
    echo "Venta #{$v->id}: total={$v->total}, costo_total={$v->costo_total}\n";
    foreach ($v->detalles as $d) {
        echo "  - {$d->descripcion} x{$d->cantidad}: precio_u={$d->precio_unitario}, costo_fifo={$d->costo_fifo}\n";
    }
}
