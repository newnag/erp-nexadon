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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action'); // created, updated, deleted, viewed, logged_in, logged_out, etc.
            $table->string('model_type')->nullable(); // App\Models\Recipe, App\Models\Ingredient, etc.
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('description'); // คำอธิบายการกระทำ
            $table->json('old_values')->nullable(); // ค่าเดิมก่อนแก้ไข
            $table->json('new_values')->nullable(); // ค่าใหม่หลังแก้ไข
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
            $table->index(['user_id', 'created_at']);
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
