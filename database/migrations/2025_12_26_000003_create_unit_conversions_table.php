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
        Schema::create('unit_conversions', function (Blueprint $table) {
            $table->id();
            $table->string('from_unit', 20)->comment('หน่วยต้นทาง เช่น g, ml');
            $table->string('to_unit', 20)->comment('หน่วยปลายทาง เช่น kg, L');
            $table->decimal('factor', 20, 10)->comment('ตัวคูณแปลงหน่วย (from * factor = to)');
            $table->timestamps();

            $table->unique(['from_unit', 'to_unit']);
            $table->index('from_unit');
            $table->index('to_unit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_conversions');
    }
};
