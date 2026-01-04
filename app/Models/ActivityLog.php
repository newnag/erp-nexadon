<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Get the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subject model.
     */
    public function subject(): MorphTo
    {
        return $this->morphTo('model');
    }

    /**
     * Get the notifications for this activity.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }

    /**
     * Get the action label in Thai.
     */
    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'created' => 'สร้าง',
            'updated' => 'แก้ไข',
            'deleted' => 'ลบ',
            'viewed' => 'ดู',
            'logged_in' => 'เข้าสู่ระบบ',
            'logged_out' => 'ออกจากระบบ',
            'stock_in' => 'รับสต็อค',
            'stock_out' => 'เบิกสต็อค',
            'stock_adjustment' => 'ปรับสต็อค',
            default => $this->action,
        };
    }

    /**
     * Get the model type label in Thai.
     */
    public function getModelTypeLabelAttribute(): string
    {
        if (!$this->model_type) {
            return '';
        }

        return match ($this->model_type) {
            'App\\Models\\User' => 'ผู้ใช้',
            'App\\Models\\Ingredient' => 'วัตถุดิบ',
            'App\\Models\\Recipe' => 'สูตรอาหาร',
            'App\\Models\\Supplier' => 'ซัพพลายเออร์',
            'App\\Models\\FinancialTransaction' => 'รายการเงิน',
            'App\\Models\\FinancialCategory' => 'หมวดหมู่การเงิน',
            'App\\Models\\InventoryTransaction' => 'รายการสต็อค',
            default => class_basename($this->model_type),
        };
    }

    /**
     * Get icon for the action type.
     */
    public function getActionIconAttribute(): string
    {
        return match ($this->action) {
            'created' => 'plus-circle',
            'updated' => 'edit',
            'deleted' => 'trash',
            'viewed' => 'eye',
            'logged_in' => 'log-in',
            'logged_out' => 'log-out',
            'stock_in' => 'package-plus',
            'stock_out' => 'package-minus',
            'stock_adjustment' => 'sliders',
            default => 'activity',
        };
    }

    /**
     * Get color for the action type.
     */
    public function getActionColorAttribute(): string
    {
        return match ($this->action) {
            'created' => 'green',
            'updated' => 'blue',
            'deleted' => 'red',
            'viewed' => 'gray',
            'logged_in' => 'purple',
            'logged_out' => 'orange',
            'stock_in' => 'emerald',
            'stock_out' => 'amber',
            'stock_adjustment' => 'indigo',
            default => 'gray',
        };
    }
}
