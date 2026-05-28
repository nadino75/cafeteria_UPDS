<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rol_permisos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rol_id');
            $table->unsignedBigInteger('modulo_id');
            $table->unsignedBigInteger('accion_id');

            $table->unique(['rol_id', 'modulo_id', 'accion_id'], 'uq_rol_modulo_accion');

            $table->foreign('rol_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('modulo_id')->references('id')->on('modulos_sistema')->onDelete('cascade');
            $table->foreign('accion_id')->references('id')->on('acciones_sistema')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rol_permisos');
    }
};
