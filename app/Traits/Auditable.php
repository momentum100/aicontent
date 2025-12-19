<?php

namespace App\Traits;

use App\Models\ActionLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait Auditable
{
    protected static function bootAuditable(): void
    {
        static::created(function ($model) {
            static::logAction('created', $model);
        });

        static::updated(function ($model) {
            static::logAction('updated', $model, $model->getOriginal());
        });

        static::deleted(function ($model) {
            static::logAction('deleted', $model);
        });
    }

    protected static function logAction(string $action, $model, array $oldValues = null): void
    {
        $newValues = $action === 'deleted' ? null : $model->getAttributes();

        ActionLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
