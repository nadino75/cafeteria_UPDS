<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_venta', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('venta_id');
            $table->enum('tipo_item', ['producto', 'menu'])->default('producto');
            $table->unsignedBigInteger('producto_id')->nullable();
            $table->unsignedBigInteger('menu_id')->nullable();
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('descuento_item', 10, 2)->default(0);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('costo_fifo', 10, 2)->default(0);

            $table->foreign('venta_id')->references('id')->on('ventas')->onDelete('cascade');
            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('restrict');
            $table->foreign('menu_id')->references('id')->on('menus')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_venta');
    }
};
