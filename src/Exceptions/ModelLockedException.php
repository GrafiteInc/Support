<?php

namespace Grafite\Support\Exceptions;

use Exception;
use Illuminate\Database\Eloquent\Model;

class ModelLockedException extends Exception
{
    protected Model $model;

    protected int $lockedByUserId;

    public function __construct(Model $model, int $lockedByUserId)
    {
        $this->model = $model;
        $this->lockedByUserId = $lockedByUserId;

        $modelClass = get_class($model);
        $modelId = $model->getKey();

        parent::__construct(
            "The {$modelClass} with ID {$modelId} is currently locked by user {$lockedByUserId}."
        );
    }

    public function getModel(): Model
    {
        return $this->model;
    }

    public function getLockedByUserId(): int
    {
        return $this->lockedByUserId;
    }
}
