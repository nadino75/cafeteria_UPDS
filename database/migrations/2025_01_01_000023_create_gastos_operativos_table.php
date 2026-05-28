<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gastos_operativos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('turno_id')->nullable();
            $table->enum('categoria', ['servicios', 'mantenimiento', 'insumos', 'nomina', 'impuestos', 'otros']);
            $table->text('descripcion');
            $table->decimal('monto', 10, 2);
            $table->timestamp('fecha')->useCurrent();
            $table->string('comprobante_url', 255)->nullable();
            $table->unsignedBigInteger('usuario_id');

            $table->foreign('turno_id')->references('id')->on('turnos')->onDelete('set null');
            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gastos_operativos');
    }
};
