<?php

namespace App\Models\Traits;

use App\Models\ActivityLog;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            $model->logActivity('created', null, $model->getAuditAttributes());
        });

        static::updated(function ($model) {
            $dirty = $model->getDirty();
            $original = array_intersect_key($model->getOriginal(), $dirty);
            $model->logActivity('updated', $original, $dirty);
        });

        static::deleted(function ($model) {
            if (method_exists($model, 'isForceDeleting') && !$model->isForceDeleting()) {
                return;
            }
            $model->logActivity('deleted', $model->getAuditAttributes(), null);
        });
    }

    public function logActivity(string $action, ?array $before, ?array $after): void
    {
        ActivityLog::create([
            'subject_type' => static::class,
            'subject_id' => $this->getKey(),
            'user_id' => self::resolveUserId(),
            'action' => $action,
            'changes' => [
                'before' => $before,
                'after' => $after,
            ],
        ]);
    }

    protected function getAuditAttributes(): array
    {
        return $this->only($this->fillable);
    }

    protected static function resolveUserId(): ?int
    {
        if (app()->runningInConsole()) {
            return null;
        }
        return request()?->attributes->get('user_id');
    }
}
