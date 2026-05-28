<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique()->nullable();
            $table->unsignedBigInteger('proveedor_id');
            $table->unsignedBigInteger('usuario_id');
            $table->timestamp('fecha_orden')->useCurrent();
            $table->timestamp('fecha_recepcion')->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('impuesto', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->enum('estado', ['pendiente', 'recibida', 'parcial', 'cancelada'])->default('pendiente');
            $table->text('nota')->nullable();

            $table->foreign('proveedor_id')->references('id')->on('proveedores')->onDelete('restrict');
            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('restrict');
        });

        // FK diferida: lotes_inventario.compra_id → compras.id
        Schema::table('lotes_inventario', function (Blueprint $table) {
            $table->foreign('compra_id')->references('id')->on('compras')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('lotes_inventario', function (Blueprint $table) {
            $table->dropForeign(['compra_id']);
        });
        Schema::dropIfExists('compras');
    }
};
