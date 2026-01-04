<?php

namespace App\Traits;

use App\Services\ActivityLogger;

trait LogsActivity
{
    /**
     * Boot the trait.
     */
    public static function bootLogsActivity(): void
    {
        // Log when model is created
        static::created(function ($model) {
            if (method_exists($model, 'getActivityLogDescription')) {
                $description = $model->getActivityLogDescription('created');
            } else {
                $modelName = class_basename($model);
                $description = "สร้าง {$modelName} ใหม่";
            }

            ActivityLogger::logCreated($model, $description);
        });

        // Log when model is updated
        static::updated(function ($model) {
            // Skip if no real changes (only updated_at changed)
            $changes = $model->getChanges();
            unset($changes['updated_at']);
            
            if (empty($changes)) {
                return;
            }

            if (method_exists($model, 'getActivityLogDescription')) {
                $description = $model->getActivityLogDescription('updated');
            } else {
                $modelName = class_basename($model);
                $description = "แก้ไข {$modelName}";
            }

            ActivityLogger::logUpdated($model, $description, $model->getOriginal());
        });

        // Log when model is deleted
        static::deleted(function ($model) {
            if (method_exists($model, 'getActivityLogDescription')) {
                $description = $model->getActivityLogDescription('deleted');
            } else {
                $modelName = class_basename($model);
                $description = "ลบ {$modelName}";
            }

            ActivityLogger::logDeleted($model, $description);
        });
    }

    /**
     * Get a custom description for the activity log.
     * Override this method in your model to customize the description.
     */
    public function getActivityLogDescription(string $action): string
    {
        $modelName = class_basename($this);
        $name = $this->name ?? $this->title ?? $this->getKey();

        return match ($action) {
            'created' => "สร้าง {$modelName}: {$name}",
            'updated' => "แก้ไข {$modelName}: {$name}",
            'deleted' => "ลบ {$modelName}: {$name}",
            default => "{$action} {$modelName}: {$name}",
        };
    }
}
