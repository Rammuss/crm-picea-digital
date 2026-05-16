<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

trait LogsActivity
{
    protected static array $activityExcluded = ['updated_at', 'password', 'remember_token'];

    protected static function bootLogsActivity(): void
    {
        static::created(function (Model $model): void {
            self::logActivity('create', $model, null, self::sanitize($model->getAttributes()));
        });

        static::updated(function (Model $model): void {
            $changes = array_diff_key($model->getChanges(), array_flip(self::$activityExcluded));
            if (empty($changes)) {
                return;
            }

            $old = [];
            foreach (array_keys($changes) as $key) {
                $old[$key] = $model->getOriginal($key);
            }

            self::logActivity('update', $model, self::sanitize($old), self::sanitize($changes));
        });

        static::deleted(function (Model $model): void {
            self::logActivity('delete', $model, null, self::sanitize($model->getAttributes()));
        });

        if (in_array('Illuminate\\Database\\Eloquent\\SoftDeletes', class_uses_recursive(static::class), true)) {
            static::restored(function (Model $model): void {
                self::logActivity('restore', $model, null, self::sanitize($model->getAttributes()));
            });

            static::forceDeleted(function (Model $model): void {
                self::logActivity('force_delete', $model, null, ['id' => $model->getKey()]);
            });
        }
    }

    private static function logActivity(string $action, Model $model, ?array $oldValues, ?array $newValues): void
    {
        ActivityLog::query()->create([
            'user_id' => auth()->id(),
            'action' => $action,
            'entity_type' => class_basename($model),
            'entity_id' => $model->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }

    private static function sanitize(array $values): array
    {
        return collect($values)
            ->except(self::$activityExcluded)
            ->map(function ($value) {
                if (is_bool($value)) {
                    return $value ? 1 : 0;
                }
                return $value;
            })
            ->all();
    }
}
