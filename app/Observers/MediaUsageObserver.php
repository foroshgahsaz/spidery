<?php

namespace App\Observers;

use App\Services\Media\MediaRegistry;
use Illuminate\Database\Eloquent\Model;

class MediaUsageObserver
{
    public function __construct(
        protected MediaRegistry $registry,
    ) {}

    public function saved(Model $model): void
    {
        $fields = config('media-library.models.'.$model::class);

        if (! is_array($fields) || $fields === []) {
            return;
        }

        $this->registry->syncModel($model, $fields);
    }

    public function deleted(Model $model): void
    {
        $this->registry->detachModel($model);
    }
}
