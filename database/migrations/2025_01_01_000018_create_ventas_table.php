<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('turno_id');
            $table->unsignedBigInteger('usuario_id');
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->timestamp('fecha')->useCurrent();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('descuento', 10, 2)->default(0);
            $table->decimal('impuesto', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->decimal('costo_total', 10, 2)->default(0);
            $table->enum('metodo_pago', ['efectivo', 'tarjeta', 'transferencia', 'mixto']);
            $table->enum('estado', ['completada', 'cancelada', 'pendiente'])->default('completada');
            $table->text('nota')->nullable();

            $table->foreign('turno_id')->references('id')->on('turnos')->onDelete('restrict');
            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('restrict');
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
