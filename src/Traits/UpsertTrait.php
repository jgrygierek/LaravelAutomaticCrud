<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Traits;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use JG\LaravelAutomaticCrud\Enums\EventAction;
use Throwable;

trait UpsertTrait
{
    abstract protected function getAllowedRequestValues(): array;

    abstract protected function getModelConnection(): ConnectionInterface;

    abstract protected function newScopedQuery(): Builder;

    abstract protected function dispatchEvent(EventAction $action, Model $model): void;

    abstract protected function getResource(Model $object): JsonResource;

    /**
     * @return array<string, mixed>
     */
    abstract protected function getUpsertAttributes(): array;

    /**
     * @throws Throwable
     */
    public function upsert(): JsonResponse
    {
        $data = $this->getAllowedRequestValues();
        $model = $this->getModelConnection()->transaction(fn (): Model => $this->upsertModel($this->getUpsertAttributes(), $data));

        $this->dispatchEvent($model->wasRecentlyCreated ? EventAction::Created : EventAction::Updated, $model);

        return $this->getResource($model)->response()->setStatusCode($model->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function upsertModel(array $attributes, array $data): Model
    {
        $query = $this->newScopedQuery();
        $conditions = array_combine($query->qualifyColumns(array_keys($attributes)), $attributes);
        $model = $query->where($conditions)->first()
            ?? $this->newUpsertModel($query, $conditions);

        $model->fill($data)->forceFill($attributes)->save();

        return $model;
    }

    /**
     * @param  array<string, mixed>  $conditions
     */
    private function newUpsertModel(Builder $query, array $conditions): Model
    {
        $model = $query->getModel();

        if ($model->newQueryWithoutScopes()->where($conditions)->exists()) {
            throw new ModelNotFoundException()->setModel($model::class);
        }

        return $query->newModelInstance();
    }
}
