<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

trait PaginationTrait
{
    abstract protected function getConfig(string $key): mixed;

    abstract protected function applyRequest(): Request;

    protected function defaultIsPaginationEnabled(): bool
    {
        return (bool) $this->getConfig('pagination.paginate');
    }

    protected function defaultItemsPerPage(): int
    {
        return (int) $this->getConfig('pagination.per_page');
    }

    protected function maxItemsPerPage(): int
    {
        return (int) $this->getConfig('pagination.max_per_page');
    }

    protected function isPaginationOverrideAllowedInQuery(): bool
    {
        return (bool) $this->getConfig('pagination.allow_pagination_override');
    }

    protected function isPerPageOverrideAllowedInQuery(): bool
    {
        return (bool) $this->getConfig('pagination.allow_per_page_override');
    }

    protected function applyPagination(Builder $query): LengthAwarePaginator|Collection
    {
        $request = $this->applyRequest();

        return $this->canPaginateResults($request)
            ? $this->paginateResults($query, $request)
            : $query->get();
    }

    private function paginateResults(Builder $query, Request $request): LengthAwarePaginator
    {
        return $query->paginate(
            $this->getPerPage($request),
            ['*'],
            'page',
            max(1, (int) $request->input('page', 1)),
        );
    }

    private function canPaginateResults(Request $request): bool
    {
        return $this->isPaginationOverrideAllowedInQuery() && $request->has('pagination')
            ? filter_var($request->input('pagination'), FILTER_VALIDATE_BOOLEAN)
            : $this->defaultIsPaginationEnabled();
    }

    private function getPerPage(Request $request): int
    {
        if (!$this->isPerPageOverrideAllowedInQuery() || !$request->filled('per_page')) {
            return max(1, $this->defaultItemsPerPage());
        }

        return max(1, min((int) $request->input('per_page'), $this->maxItemsPerPage()));
    }
}
