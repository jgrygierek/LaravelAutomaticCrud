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
        $model = DB::transaction(fn (): Model => $this->getModelClass()::create(
            $this->getAllowedRequestValues(),
        ));

        $this->dispatchEvent(EventAction::Created, $model);

        return $this->getResource($model)->response()->setStatusCode(201);
    }

    public function show(int|string $id): JsonResource
    {
        $this->validateRequest();

        return $this->getResource($this->findModel($id));
    }

    /**
     * @throws Throwable
     */
    public function update(int|string $id): JsonResource
    {
        $model = DB::transaction(fn (): Model => tap(
            $this->findModel($id),
            fn (Model $model) => $model->update($this->getAllowedRequestValues()),
        ));

        $this->dispatchEvent(EventAction::Updated, $model);

        return $this->getResource($model);
    }

    /**
     * @throws Throwable
     */
    public function destroy(int|string $id): Response
    {
        $this->validateRequest();

        $model = DB::transaction(fn (): Model => tap(
            $this->findModel($id),
            fn (Model $model) => $model->delete(),
        ));

        $this->dispatchEvent(EventAction::Deleted, $model);

        return response()->noContent();
    }

    protected function validateRequest(): void
    {
        $this->applyRequest();
    }

    protected function findModel(int|string $id): Model
    {
        $query = $this
            ->modifyQuery($this->getModelClass()::query())
            ->withoutGlobalScopes($this->withoutGlobalScopes());

        return $query->where($this->resolveRouteKeyColumn($query), $id)->firstOrFail();
    }

    private function resolveRouteKeyColumn(Builder $query): string
    {
        $route = Route::getCurrentRoute();
        $parameterName = $route ? last($route->parameterNames()) : null;

        return ($parameterName ? $route->bindingFieldFor($parameterName) : null)
            ?? $query->getModel()->getRouteKeyName();
    }
}
