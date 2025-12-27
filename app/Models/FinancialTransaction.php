<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialTransaction extends Model
{
    protected $fillable = [
        'category_id',
        'user_id',
        'type',
        'amount',
        'description',
        'notes',
        'transaction_date',
        'reference_number',
        'payment_method',
        'attachment_path',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    /**
     * หมวดหมู่ของธุรกรรม
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(FinancialCategory::class, 'category_id');
    }

    /**
     * ผู้บันทึกธุรกรรม
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope สำหรับรายรับ
     */
    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    /**
     * Scope สำหรับรายจ่าย
     */
    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    /**
     * Scope สำหรับช่วงวันที่
     */
    public function scopeDateBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    /**
     * คำนวณยอดรวมรายรับ
     */
    public static function totalIncome($startDate = null, $endDate = null)
    {
        $query = static::income();
        if ($startDate && $endDate) {
            $query->dateBetween($startDate, $endDate);
        }
        return $query->sum('amount');
    }

    /**
     * คำนวณยอดรวมรายจ่าย
     */
    public static function totalExpense($startDate = null, $endDate = null)
    {
        $query = static::expense();
        if ($startDate && $endDate) {
            $query->dateBetween($startDate, $endDate);
        }
        return $query->sum('amount');
    }

    /**
     * คำนวณยอดคงเหลือ (รายรับ - รายจ่าย)
     */
    public static function balance($startDate = null, $endDate = null)
    {
        return static::totalIncome($startDate, $endDate) - static::totalExpense($startDate, $endDate);
    }
}
