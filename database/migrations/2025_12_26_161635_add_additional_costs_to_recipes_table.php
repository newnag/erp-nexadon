<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->decimal('labor_cost', 10, 2)->default(0)->after('total_cost')->comment('ค่าแรงงาน');
            $table->decimal('overhead_cost', 10, 2)->default(0)->after('labor_cost')->comment('ค่าใช้จ่ายการผลิต');
            $table->decimal('packaging_cost', 10, 2)->default(0)->after('overhead_cost')->comment('ค่าบรรจุภัณฑ์');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn(['labor_cost', 'overhead_cost', 'packaging_cost']);
        });
    }
};
