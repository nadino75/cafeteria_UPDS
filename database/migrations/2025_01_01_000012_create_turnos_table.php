<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turnos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique()->nullable();
            $table->unsignedBigInteger('usuario_apertura');
            $table->unsignedBigInteger('usuario_cierre')->nullable();
            $table->timestamp('fecha_apertura')->useCurrent();
            $table->timestamp('fecha_cierre')->nullable();
            $table->decimal('caja_inicial', 10, 2)->default(0);
            $table->decimal('total_ventas_esperado', 10, 2)->default(0);
            $table->decimal('total_gastos_turno', 10, 2)->default(0);
            $table->decimal('caja_final_esperada', 10, 2)->default(0);
            $table->decimal('caja_final_real', 10, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->enum('estado', ['abierto', 'en_corte', 'cerrado'])->default('abierto');
            $table->unsignedBigInteger('cierre_diario_id')->nullable();

            $table->foreign('usuario_apertura')->references('id')->on('usuarios')->onDelete('restrict');
            $table->foreign('usuario_cierre')->references('id')->on('usuarios')->onDelete('set null');
            $table->foreign('cierre_diario_id')->references('id')->on('cierres_diarios')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turnos');
    }
};
