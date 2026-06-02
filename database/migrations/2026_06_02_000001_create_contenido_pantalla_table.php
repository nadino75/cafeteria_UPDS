<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contenido_pantalla', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 255);
            $table->enum('tipo', ['video', 'imagen']);
            $table->text('archivo_url');
            $table->unsignedInteger('duracion_segundos')->default(10);
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contenido_pantalla');
    }
};
