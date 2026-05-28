<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cierres_diarios', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->unique();
            $table->unsignedBigInteger('usuario_id');
            $table->decimal('total_ventas', 10, 2)->default(0);
            $table->decimal('total_ventas_efectivo', 10, 2)->default(0);
            $table->decimal('total_ventas_tarjeta', 10, 2)->default(0);
            $table->decimal('total_ventas_transferencia', 10, 2)->default(0);
            $table->decimal('total_descuentos', 10, 2)->default(0);
            $table->decimal('total_impuestos', 10, 2)->default(0);
            $table->decimal('total_compras', 10, 2)->default(0);
            $table->decimal('total_gastos_operativos', 10, 2)->default(0);
            $table->integer('num_ventas')->default(0);
            $table->integer('num_turnos')->default(0);
            $table->text('observaciones')->nullable();
            $table->enum('estado', ['borrador', 'cerrado'])->default('borrador');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cierres_diarios');
    }
};
