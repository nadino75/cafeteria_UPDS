<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('balance_diario', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->unique();
            $table->unsignedBigInteger('cierre_diario_id')->nullable();
            $table->decimal('ingresos_ventas', 10, 2)->default(0);
            $table->decimal('otros_ingresos', 10, 2)->default(0);
            $table->decimal('total_ingresos', 10, 2)->default(0);
            $table->decimal('costo_mercancia_vendida', 10, 2)->default(0);
            $table->decimal('gastos_operativos', 10, 2)->default(0);
            $table->decimal('gastos_nomina', 10, 2)->default(0);
            $table->decimal('otros_gastos', 10, 2)->default(0);
            $table->decimal('total_egresos', 10, 2)->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('cierre_diario_id')->references('id')->on('cierres_diarios')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('balance_diario');
    }
};
