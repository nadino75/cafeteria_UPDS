<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE ventas MODIFY COLUMN metodo_pago ENUM('efectivo', 'tarjeta', 'transferencia', 'qr', 'mixto') NOT NULL DEFAULT 'efectivo'");

        Schema::table('ventas', function (Blueprint $table) {
            $table->decimal('pago_qr', 10, 2)->nullable()->after('pago_transferencia');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn('pago_qr');
        });

        DB::statement("ALTER TABLE ventas MODIFY COLUMN metodo_pago ENUM('efectivo', 'tarjeta', 'transferencia', 'mixto') NOT NULL");
    }
};
