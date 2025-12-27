<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update enum to include adjustment_in and adjustment_out
        DB::statement("ALTER TABLE inventory_transactions MODIFY COLUMN type ENUM('purchase', 'usage', 'adjustment', 'waste', 'adjustment_in', 'adjustment_out') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE inventory_transactions MODIFY COLUMN type ENUM('purchase', 'usage', 'adjustment', 'waste') NOT NULL");
    }
};
