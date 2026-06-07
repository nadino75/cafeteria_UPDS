<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->decimal('vuelto', 10, 2)->default(0)->after('pago_qr');
        });

        Schema::table('cortes_caja', function (Blueprint $table) {
            $table->decimal('total_qr', 10, 2)->default(0)->after('total_transferencia');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn('vuelto');
        });

        Schema::table('cortes_caja', function (Blueprint $table) {
            $table->dropColumn('total_qr');
        });
    }
};
