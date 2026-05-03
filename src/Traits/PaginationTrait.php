<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

trait PaginationTrait
{
    abstract protected function getConfig(string $key): mixed;

    protected function defaultIsPaginationEnabled(): bool
    {
        return (bool) $this->getConfig('pagination.paginate');
    }

    protected function defaultItemsPerPage(): int
    {
        return (int) $this->getConfig('pagination.per_page');
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
        return $this->canPaginateResults()
            ? $this->paginateResults($query)
            : $query->get();
    }

    private function paginateResults(Builder $query): LengthAwarePaginator
    {
        return $query->paginate(
            $this->getPerPage(),
            ['*'],
            'page',
            max(1, (int) request()->input('page', 1)),
        );
    }

    private function canPaginateResults(): bool
    {
        return $this->isPaginationOverrideAllowedInQuery() && request()->has('pagination')
            ? filter_var(request()->input('pagination'), FILTER_VALIDATE_BOOLEAN)
            : $this->defaultIsPaginationEnabled();
    }

    private function getPerPage(): int
    {
        $perPage = $this->isPerPageOverrideAllowedInQuery() && request()->filled('per_page')
            ? (int) request()->input('per_page')
            : $this->defaultItemsPerPage();

        return max(1, $perPage);
    }
}
