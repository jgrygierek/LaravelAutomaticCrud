<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use JG\LaravelAutomaticCrud\Traits\ConfigTrait;
use JG\LaravelAutomaticCrud\Traits\ModelTrait;
use JG\LaravelAutomaticCrud\Traits\PaginationTrait;
use JG\LaravelAutomaticCrud\Traits\RequestTrait;
use JG\LaravelAutomaticCrud\Traits\ResourceTrait;
use JG\LaravelAutomaticCrud\Traits\SearchTrait;
use Throwable;

abstract class CrudableController extends Controller
{
    use ConfigTrait,
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
        return DB::transaction(function () {
            return $this
                ->getResource(
                    $this->getModelClass()::create(
                        $this->getAllowedRequestValues(),
                    ),
                )
                ->response()
                ->setStatusCode(201);
        });
    }

    public function show(int|string $id): JsonResource
    {
        $model = $this->findModel($id);
        $this->applyRequest();

        return $this->getResource($model);
    }

    /**
     * @throws Throwable
     */
    public function update(int|string $id): JsonResource
    {
        return DB::transaction(fn () => $this->getResource(
            tap(
                $this->findModel($id),
                fn (Model $model) => $model->update($this->getAllowedRequestValues()),
            ),
        ));
    }

    /**
     * @throws Throwable
     */
    public function destroy(int|string $id): Response
    {
        $model = $this->findModel($id);
        $this->applyRequest();

        DB::transaction(static fn () => $model->delete());

        return response()->noContent();
    }

    private function findModel(int|string $id): Model
    {
        return $this->getModelClass()::findOrFail($id);
    }
}
