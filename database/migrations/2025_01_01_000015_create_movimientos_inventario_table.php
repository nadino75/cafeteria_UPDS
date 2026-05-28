<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('lote_id')->nullable();
            $table->enum('tipo', ['entrada', 'salida', 'ajuste', 'merma', 'devolucion']);
            $table->integer('cantidad');
            $table->decimal('costo_unitario', 10, 2)->nullable();
            $table->string('motivo', 255)->nullable();
            $table->unsignedBigInteger('usuario_id');
            $table->timestamp('fecha')->useCurrent();
            $table->string('referencia_tipo', 50)->nullable();
            $table->unsignedInteger('referencia_id')->nullable();

            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('restrict');
            $table->foreign('lote_id')->references('id')->on('lotes_inventario')->onDelete('set null');
            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};
