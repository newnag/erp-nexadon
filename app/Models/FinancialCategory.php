<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialCategory extends Model
{
    protected $fillable = [
        'name',
        'type',
        'description',
        'color',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * รายการธุรกรรมในหมวดหมู่นี้
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class, 'category_id');
    }

    /**
     * Scope สำหรับหมวดหมู่ที่ใช้งานอยู่
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope สำหรับหมวดหมู่รายรับ
     */
    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    /**
     * Scope สำหรับหมวดหมู่รายจ่าย
     */
    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }
}
