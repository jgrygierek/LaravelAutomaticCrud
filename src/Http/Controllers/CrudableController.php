<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use JG\LaravelAutomaticCrud\Enums\EventAction;
use JG\LaravelAutomaticCrud\Traits\ConfigTrait;
use JG\LaravelAutomaticCrud\Traits\EventTrait;
use JG\LaravelAutomaticCrud\Traits\ModelTrait;
use JG\LaravelAutomaticCrud\Traits\PaginationTrait;
use JG\LaravelAutomaticCrud\Traits\RequestTrait;
use JG\LaravelAutomaticCrud\Traits\ResourceTrait;
use JG\LaravelAutomaticCrud\Traits\SearchTrait;
use Throwable;

abstract class CrudableController extends Controller
{
    use ConfigTrait,
        EventTrait,
        ModelTrait,
        PaginationTrait,
        RequestTrait,
        ResourceTrait,
        SearchTrait;

    public function index(): JsonResource
    {
        return $this->getCollectionResource(
            $this->applyPagination(
                $this->buildQuery($this->applyRequest()),
            ),
        );
    }

    /**
     * @throws Throwable
     */
    public function store(): JsonResponse
    {
        $data = $this->getAllowedRequestValues();
        $model = DB::transaction(fn (): Model => $this->createModel($data));

        $this->dispatchEvent(EventAction::Created, $model);

        return $this->getResource($model)->response()->setStatusCode(201);
    }

    public function show(int|string $id): JsonResource
    {
        $model = $this->findModel($id);
        $this->validateRequest();

        return $this->getResource($model);
    }

    /**
     * @throws Throwable
     */
    public function update(int|string $id): JsonResource
    {
        $model = DB::transaction(function () use ($id): Model {
            return $this->updateModel(
                $this->findModel($id),
                $this->getAllowedRequestValues(),
            );
        });

        $this->dispatchEvent(EventAction::Updated, $model);

        return $this->getResource($model);
    }

    /**
     * @throws Throwable
     */
    public function destroy(int|string $id): Response
    {
        $model = DB::transaction(function () use ($id): Model {
            $model = $this->findModel($id);
            $this->validateRequest();

            return $this->deleteModel($model);
        });

        $this->dispatchEvent(EventAction::Deleted, $model);

        return response()->noContent();
    }

    protected function createModel(array $data): Model
    {
        return $this->getModelClass()::create($data);
    }

    protected function updateModel(Model $model, array $data): Model
    {
        $model->update($data);

        return $model;
    }

    protected function deleteModel(Model $model): Model
    {
        $model->delete();

        return $model;
    }

    protected function validateRequest(): void
    {
        $this->applyRequest();
    }

    protected function findModel(int|string $id): Model
    {
        $query = $this->newScopedQuery();

        return $query->where($this->resolveRouteKeyColumn($query), $id)->firstOrFail();
    }

    protected function newScopedQuery(): Builder
    {
        return $this
            ->modifyQuery($this->getModelClass()::query())
            ->withoutGlobalScopes($this->withoutGlobalScopes());
    }

    protected function resolveRouteKeyColumn(Builder $query): string
    {
        $route = Route::getCurrentRoute();
        $parameterName = $route ? last($route->parameterNames()) : null;

        return ($parameterName ? $route->bindingFieldFor($parameterName) : null)
            ?? $query->getModel()->getRouteKeyName();
    }
}
