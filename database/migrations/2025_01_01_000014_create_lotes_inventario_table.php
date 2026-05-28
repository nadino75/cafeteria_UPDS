<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lotes_inventario', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('compra_id')->nullable();
            $table->string('numero_lote', 50)->nullable();
            $table->timestamp('fecha_entrada')->useCurrent();
            $table->date('fecha_vencimiento')->nullable();
            $table->integer('cantidad_inicial');
            $table->integer('cantidad_disponible');
            $table->decimal('costo_unitario', 10, 2);
            $table->enum('estado', ['disponible', 'agotado', 'vencido'])->default('disponible');

            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('restrict');
        });

        // Índice FIFO: ordenar por producto, estado y fecha_entrada ASC
        \DB::statement('CREATE INDEX idx_lote_fifo ON lotes_inventario (producto_id, estado, fecha_entrada ASC)');
    }

    public function down(): void
    {
        Schema::dropIfExists('lotes_inventario');
    }
};
