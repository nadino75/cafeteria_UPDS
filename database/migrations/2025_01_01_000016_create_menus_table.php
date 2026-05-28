<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->unsignedBigInteger('categoria_id')->nullable();
            $table->decimal('precio_venta', 10, 2);
            $table->string('imagen_url', 255)->nullable();
            $table->time('disponible_desde')->default('06:00:00');
            $table->time('disponible_hasta')->default('22:00:00');
            $table->boolean('activo')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('categoria_id')->references('id')->on('categorias')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
