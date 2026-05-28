<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cortes_caja', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('turno_id')->unique();
            $table->unsignedBigInteger('usuario_id');
            $table->timestamp('fecha_corte')->useCurrent();
            $table->integer('billetes_200')->default(0);
            $table->integer('billetes_100')->default(0);
            $table->integer('billetes_50')->default(0);
            $table->integer('billetes_20')->default(0);
            $table->integer('billetes_10')->default(0);
            $table->decimal('monedas_total', 10, 2)->default(0);
            $table->decimal('total_efectivo_contado', 10, 2);
            $table->decimal('total_tarjeta', 10, 2)->default(0);
            $table->decimal('total_transferencia', 10, 2)->default(0);
            $table->decimal('total_real', 10, 2);
            $table->text('observaciones')->nullable();

            $table->foreign('turno_id')->references('id')->on('turnos')->onDelete('restrict');
            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cortes_caja');
    }
};
