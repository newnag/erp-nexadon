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
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('financial_categories')->onDelete('restrict');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('type', ['income', 'expense'])->comment('income = รายรับ, expense = รายจ่าย');
            $table->decimal('amount', 15, 2);
            $table->string('description');
            $table->text('notes')->nullable();
            $table->date('transaction_date');
            $table->string('reference_number')->nullable()->comment('เลขที่อ้างอิง เช่น เลขใบเสร็จ');
            $table->string('payment_method')->nullable()->comment('วิธีชำระเงิน: cash, transfer, credit_card');
            $table->string('attachment_path')->nullable()->comment('ไฟล์แนบ เช่น ใบเสร็จ');
            $table->timestamps();
            
            $table->index(['transaction_date', 'type']);
            $table->index('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
    }
};
