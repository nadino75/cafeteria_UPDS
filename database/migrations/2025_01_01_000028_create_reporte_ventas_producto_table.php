<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reporte_ventas_producto', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->unsignedBigInteger('producto_id')->nullable();
            $table->unsignedBigInteger('menu_id')->nullable();
            $table->string('nombre_item', 100)->nullable();
            $table->integer('cantidad_vendida')->default(0);
            $table->decimal('ingresos_total', 10, 2)->default(0);
            $table->decimal('costo_total_fifo', 10, 2)->default(0);

            $table->index('fecha', 'idx_rvp_fecha');
            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('set null');
            $table->foreign('menu_id')->references('id')->on('menus')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reporte_ventas_producto');
    }
};
