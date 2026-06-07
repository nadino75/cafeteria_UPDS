<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL ENUM doesn't allow ALTER COLUMN type directly in all versions
        // We use raw SQL to change column types

        DB::statement('ALTER TABLE lotes_inventario MODIFY COLUMN cantidad_inicial DECIMAL(10,4) NOT NULL');
        DB::statement('ALTER TABLE lotes_inventario MODIFY COLUMN cantidad_disponible DECIMAL(10,4) NOT NULL');

        DB::statement('ALTER TABLE detalle_venta_lotes MODIFY COLUMN cantidad_consumida DECIMAL(10,4) NOT NULL');

        DB::statement('ALTER TABLE movimientos_inventario MODIFY COLUMN cantidad DECIMAL(10,4) NOT NULL');

        DB::statement('ALTER TABLE productos MODIFY COLUMN stock_actual DECIMAL(10,4) NOT NULL DEFAULT 0');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE lotes_inventario MODIFY COLUMN cantidad_inicial INT NOT NULL');
        DB::statement('ALTER TABLE lotes_inventario MODIFY COLUMN cantidad_disponible INT NOT NULL');

        DB::statement('ALTER TABLE detalle_venta_lotes MODIFY COLUMN cantidad_consumida INT NOT NULL');

        DB::statement('ALTER TABLE movimientos_inventario MODIFY COLUMN cantidad INT NOT NULL');

        DB::statement('ALTER TABLE productos MODIFY COLUMN stock_actual INT NOT NULL DEFAULT 0');
    }
};
