<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->decimal('pago_efectivo', 10, 2)->nullable()->after('total');
            $table->decimal('pago_tarjeta', 10, 2)->nullable()->after('pago_efectivo');
            $table->decimal('pago_transferencia', 10, 2)->nullable()->after('pago_tarjeta');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn(['pago_efectivo', 'pago_tarjeta', 'pago_transferencia']);
        });
    }
};
