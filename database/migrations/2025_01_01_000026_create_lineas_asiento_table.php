<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lineas_asiento', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asiento_id');
            $table->unsignedBigInteger('cuenta_id');
            $table->enum('tipo', ['debito', 'credito']);
            $table->decimal('monto', 10, 2);
            $table->string('descripcion', 255)->nullable();

            $table->foreign('asiento_id')->references('id')->on('asientos_contables')->onDelete('cascade');
            $table->foreign('cuenta_id')->references('id')->on('cuentas_contables')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lineas_asiento');
    }
};
