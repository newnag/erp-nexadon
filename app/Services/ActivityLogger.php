<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    /**
     * Log an activity and optionally notify users.
     */
    public static function log(
        string $action,
        string $description,
        ?Model $model = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        bool $notifyOthers = true
    ): ActivityLog {
        $activityLog = ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->getKey(),
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);

        // Notify other users
        if ($notifyOthers) {
            self::notifyUsers($activityLog);
        }

        return $activityLog;
    }

    /**
     * Log a model creation.
     */
    public static function logCreated(Model $model, string $description, bool $notifyOthers = true): ActivityLog
    {
        return self::log(
            'created',
            $description,
            $model,
            null,
            $model->toArray(),
            $notifyOthers
        );
    }

    /**
     * Log a model update.
     */
    public static function logUpdated(Model $model, string $description, array $oldValues, bool $notifyOthers = true): ActivityLog
    {
        // Only log changed values
        $changedValues = [];
        foreach ($model->getChanges() as $key => $value) {
            if ($key !== 'updated_at') {
                $changedValues[$key] = $value;
            }
        }

        return self::log(
            'updated',
            $description,
            $model,
            array_intersect_key($oldValues, $changedValues),
            $changedValues,
            $notifyOthers
        );
    }

    /**
     * Log a model deletion.
     */
    public static function logDeleted(Model $model, string $description, bool $notifyOthers = true): ActivityLog
    {
        return self::log(
            'deleted',
            $description,
            $model,
            $model->toArray(),
            null,
            $notifyOthers
        );
    }

    /**
     * Log user login.
     */
    public static function logLogin(User $user): ActivityLog
    {
        return self::log(
            'logged_in',
            "ผู้ใช้ {$user->name} เข้าสู่ระบบ",
            $user,
            null,
            null,
            true
        );
    }

    /**
     * Log user logout.
     */
    public static function logLogout(User $user): ActivityLog
    {
        return self::log(
            'logged_out',
            "ผู้ใช้ {$user->name} ออกจากระบบ",
            $user,
            null,
            null,
            false
        );
    }

    /**
     * Log stock in.
     */
    public static function logStockIn(Model $model, string $description, array $details): ActivityLog
    {
        return self::log(
            'stock_in',
            $description,
            $model,
            null,
            $details,
            true
        );
    }

    /**
     * Log stock out.
     */
    public static function logStockOut(Model $model, string $description, array $details): ActivityLog
    {
        return self::log(
            'stock_out',
            $description,
            $model,
            null,
            $details,
            true
        );
    }

    /**
     * Log stock adjustment.
     */
    public static function logStockAdjustment(Model $model, string $description, array $oldValues, array $newValues): ActivityLog
    {
        return self::log(
            'stock_adjustment',
            $description,
            $model,
            $oldValues,
            $newValues,
            true
        );
    }

    /**
     * Notify all other users about the activity.
     * Uses a single bulk INSERT to avoid N individual write transactions (critical for SQLite performance).
     */
    protected static function notifyUsers(ActivityLog $activityLog): void
    {
        $currentUserId = Auth::id();
        $userName = Auth::user()?->name ?? 'ระบบ';

        // Get only the IDs to keep the query lightweight
        $userIds = User::when($currentUserId, function ($query) use ($currentUserId) {
            return $query->where('id', '!=', $currentUserId);
        })->pluck('id');

        if ($userIds->isEmpty()) {
            return;
        }

        $title = self::getNotificationTitle($activityLog, $userName);
        $type = self::getNotificationType($activityLog->action);
        $link = self::getNotificationLink($activityLog);
        $now = now()->toDateTimeString();

        // Single bulk INSERT — one DB round-trip instead of N
        $records = $userIds->map(fn($uid) => [
            'user_id'          => $uid,
            'activity_log_id'  => $activityLog->id,
            'title'            => $title,
            'message'          => $activityLog->description,
            'type'             => $type,
            'link'             => $link,
            'created_at'       => $now,
            'updated_at'       => $now,
        ])->all();

        DB::table('user_notifications')->insert($records);
    }

    /**
     * Get notification title based on action.
     */
    protected static function getNotificationTitle(ActivityLog $activityLog, string $userName): string
    {
        return match ($activityLog->action) {
            'created' => "{$userName} สร้าง{$activityLog->model_type_label}ใหม่",
            'updated' => "{$userName} แก้ไข{$activityLog->model_type_label}",
            'deleted' => "{$userName} ลบ{$activityLog->model_type_label}",
            'logged_in' => "{$userName} เข้าสู่ระบบ",
            'logged_out' => "{$userName} ออกจากระบบ",
            'stock_in' => "{$userName} รับสต็อค",
            'stock_out' => "{$userName} เบิกสต็อค",
            'stock_adjustment' => "{$userName} ปรับสต็อค",
            default => "{$userName} ดำเนินการ",
        };
    }

    /**
     * Get notification type based on action.
     */
    protected static function getNotificationType(string $action): string
    {
        return match ($action) {
            'created', 'stock_in' => 'success',
            'deleted', 'stock_out' => 'warning',
            'logged_in', 'logged_out' => 'info',
            default => 'info',
        };
    }

    /**
     * Get notification link based on activity.
     */
    protected static function getNotificationLink(ActivityLog $activityLog): ?string
    {
        if (!$activityLog->model_type || !$activityLog->model_id) {
            return null;
        }

        if ($activityLog->action === 'deleted') {
            return null;
        }

        return match ($activityLog->model_type) {
            'App\\Models\\Ingredient' => "/ingredients/{$activityLog->model_id}",
            'App\\Models\\Recipe' => "/recipes/{$activityLog->model_id}",
            'App\\Models\\FinancialTransaction' => "/finance/{$activityLog->model_id}/edit",
            default => null,
        };
    }
}
