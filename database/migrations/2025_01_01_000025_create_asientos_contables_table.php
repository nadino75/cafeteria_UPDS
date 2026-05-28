<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asientos_contables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cierre_diario_id')->nullable();
            $table->date('fecha');
            $table->string('numero_asiento', 20)->unique()->nullable();
            $table->text('descripcion');
            $table->unsignedBigInteger('usuario_id');
            $table->enum('estado', ['borrador', 'confirmado', 'anulado'])->default('borrador');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('cierre_diario_id')->references('id')->on('cierres_diarios')->onDelete('set null');
            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asientos_contables');
    }
};
