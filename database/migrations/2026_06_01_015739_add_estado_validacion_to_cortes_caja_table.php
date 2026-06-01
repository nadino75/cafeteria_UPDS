<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cortes_caja', function (Blueprint $table) {
            $table->decimal('efectivo_esperado', 10, 2)->nullable()->after('total_efectivo_contado');
            $table->enum('estado', ['pendiente', 'entregado'])->default('pendiente')->after('total_real');
            $table->unsignedBigInteger('validado_por')->nullable()->after('estado');
            $table->timestamp('fecha_validacion')->nullable()->after('validado_por');

            $table->foreign('validado_por')->references('id')->on('usuarios')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('cortes_caja', function (Blueprint $table) {
            $table->dropForeign(['validado_por']);
            $table->dropColumn(['efectivo_esperado', 'estado', 'validado_por', 'fecha_validacion']);
        });
    }
};
