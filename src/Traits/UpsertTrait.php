<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use JG\LaravelAutomaticCrud\Enums\EventAction;
use Throwable;

trait UpsertTrait
{
    abstract protected function getAllowedRequestValues(): array;

    abstract protected function newScopedQuery(): Builder;

    abstract protected function resolveRouteKeyColumn(Builder $query): string;

    abstract protected function dispatchEvent(EventAction $action, Model $model): void;

    abstract protected function getResource(Model $object): JsonResource;

    /**
     * @throws Throwable
     */
    public function upsert(int|string $id): JsonResponse
    {
        $data = $this->getAllowedRequestValues();
        $model = DB::transaction(fn (): Model => $this->upsertModel($id, $data));

        $this->dispatchEvent($model->wasRecentlyCreated ? EventAction::Created : EventAction::Updated, $model);

        return $this->getResource($model)->response()->setStatusCode($model->wasRecentlyCreated ? 201 : 200);
    }

    protected function upsertModel(int|string $id, array $data): Model
    {
        $query = $this->newScopedQuery();
        $column = $this->resolveRouteKeyColumn($query);

        return $query->updateOrCreate([$column => $id], $data);
    }
}
