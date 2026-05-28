<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reporte_stock', function (Blueprint $table) {
            $table->id();
            $table->timestamp('generado_en')->useCurrent();
            $table->unsignedBigInteger('producto_id');
            $table->integer('stock_actual')->nullable();
            $table->integer('stock_minimo')->nullable();
            $table->boolean('alerta_stock_bajo')->default(false);
            $table->integer('lotes_proximos_vencer')->default(0);
            $table->decimal('valor_inventario', 10, 2)->nullable();

            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reporte_stock');
    }
};
