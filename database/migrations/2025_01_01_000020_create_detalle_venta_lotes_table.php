<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_venta_lotes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('detalle_venta_id');
            $table->unsignedBigInteger('lote_id');
            $table->integer('cantidad_consumida');
            $table->decimal('costo_unitario_lote', 10, 2);

            $table->foreign('detalle_venta_id')->references('id')->on('detalle_venta')->onDelete('cascade');
            $table->foreign('lote_id')->references('id')->on('lotes_inventario')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_venta_lotes');
    }
};
