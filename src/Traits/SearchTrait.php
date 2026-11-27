<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use JG\LaravelAutomaticCrud\Enums\SortDirection;
use JG\LaravelAutomaticCrud\Helpers\QueryHelper;
use JG\LaravelAutomaticCrud\Http\Interfaces\SearchableInterface;

trait SearchTrait
{
    /**
     * @var array<int, string>|null
     */
    private ?array $resolvedDefaultSortableColumns = null;

    abstract protected function getModelClass(): string;

    abstract protected function getConfigName(): string;

    protected function buildQuery(Request $request): Builder
    {
        $modelClass = $this->getModelClass();
        $query = $modelClass::query();

        if ($this->canAddSearchFilters($modelClass)) {
            $query = $this->pipelineSearch($query, new $modelClass());
        }

        $query = $this
            ->modifyQuery($query)
            ->withoutGlobalScopes($this->withoutGlobalScopes());

        return $this->applySorting($query, $request);
    }

    protected function modifyQuery(Builder $query): Builder
    {
        return $query;
    }

    /**
     * @return list<class-string<Scope>|string>
     */
    protected function withoutGlobalScopes(): array
    {
        return [];
    }

    /**
     * @return array<int, string>
     */
    protected function getIndexSortableColumns(): array
    {
        return $this->defaultSortableColumns();
    }

    /**
     * @return array<int, string>
     */
    protected function defaultSortableColumns(): array
    {
        return $this->resolvedDefaultSortableColumns ??= $this->resolveDefaultSortableColumns();
    }

    protected function pipelineSearch(Builder $query, SearchableInterface $object): Builder
    {
        return app(Pipeline::class)
            ->send($query)
            ->through($this->resolveSearchFilters($object))
            ->thenReturn();
    }

    private function resolveSearchFilters(SearchableInterface $object): array
    {
        $filters = $object->searchFilters();

        if (empty($filters) || !is_array(reset($filters))) {
            return $filters;
        }

        return $filters[$this->getConfigName()] ?? $filters['default'] ?? [];
    }

    private function canAddSearchFilters(string $modelClass): bool
    {
        return is_a($modelClass, SearchableInterface::class, true);
    }

    private function canAddSorting(Builder $query, Request $request): bool
    {
        return !QueryHelper::isSorted($query) && $request->filled('sort_by');
    }

    private function applySorting(Builder $query, Request $request): Builder
    {
        if ($this->canAddSorting($query, $request)) {
            $this->validateSorting($request);
            $query->orderBy($request->input('sort_by'), $this->resolveSortDirection($request)->value);
        }

        return $query;
    }

    private function validateSorting(Request $request): void
    {
        $direction = $request->input('sort_direction');

        Validator::make(
            [
                'sort_by' => $request->input('sort_by'),
                'sort_direction' => is_string($direction) ? strtolower($direction) : $direction,
            ],
            [
                'sort_by' => [Rule::in($this->resolveSortableColumns())],
                'sort_direction' => ['nullable', Rule::enum(SortDirection::class)],
            ],
        )->validate();
    }

    /**
     * @return array<int, string>
     */
    private function resolveSortableColumns(): array
    {
        $method = sprintf('get%sSortableColumns', Str::ucfirst((string) Route::getCurrentRoute()?->getActionMethod()));

        return method_exists($this, $method) ? $this->{$method}() : $this->getIndexSortableColumns();
    }

    /**
     * @return array<int, string>
     */
    private function resolveDefaultSortableColumns(): array
    {
        $model = new ($this->getModelClass())();
        $columns = $model->getConnection()->getSchemaBuilder()->getColumnListing($model->getTable());
        $visible = $model->getVisible();

        return array_diff($visible === [] ? $columns : array_intersect($columns, $visible), $model->getHidden());
    }

    private function resolveSortDirection(Request $request): SortDirection
    {
        $direction = $request->input('sort_direction');

        return is_string($direction)
            ? SortDirection::tryFrom(strtolower($direction)) ?? SortDirection::Asc
            : SortDirection::Asc;
    }
}
