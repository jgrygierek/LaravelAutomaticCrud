<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Http\Request;
use Illuminate\Routing\Pipeline;
use JG\LaravelAutomaticCrud\Http\Interfaces\SearchableInterface;

trait SearchTrait
{
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
        return !$this->isAlreadySorted($query) && $request->filled('sort_by');
    }

    private function applySorting(Builder $query, Request $request): Builder
    {
        if ($this->canAddSorting($query, $request)) {
            $query->orderBy(
                $request->input('sort_by', 'id'),
                $request->input('sort_direction', 'asc'),
            );
        }

        return $query;
    }

    private function isAlreadySorted(Builder $query): bool
    {
        return !empty($query->getQuery()->orders);
    }
}
