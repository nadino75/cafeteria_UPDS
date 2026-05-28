<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reporte_mensual', function (Blueprint $table) {
            $table->id();
            $table->year('anio');
            $table->tinyInteger('mes')->unsigned();
            $table->decimal('total_ventas', 10, 2)->default(0);
            $table->decimal('total_costo_mercancia', 10, 2)->default(0);
            $table->decimal('total_gastos_operativos', 10, 2)->default(0);
            $table->decimal('utilidad_bruta', 10, 2)->default(0);
            $table->decimal('utilidad_neta', 10, 2)->default(0);
            $table->integer('num_ventas')->default(0);
            $table->decimal('ticket_promedio', 10, 2)->default(0);
            $table->string('producto_mas_vendido', 100)->nullable();
            $table->timestamp('generado_en')->useCurrent();

            $table->unique(['anio', 'mes'], 'uq_anio_mes');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reporte_mensual');
    }
};
