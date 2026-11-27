<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JG\LaravelAutomaticCrud\Helpers\QueryHelper;
use JG\LaravelAutomaticCrud\Http\Resources\AutocompleteResource;
use RuntimeException;

trait AutocompleteTrait
{
    /**
     * @var class-string<JsonResource>|null
     */
    private ?string $resolvedResourceAutocompleteClass = null;

    abstract protected function getConfig(string $key): mixed;

    abstract protected function applyRequest(): Request;

    abstract protected function buildQuery(Request $request): Builder;

    abstract protected function resolveResourceClass(string $suffix): ?string;

    /**
     * @return array<int, string>
     */
    abstract protected function getIndexSortableColumns(): array;

    /**
     * @return list<string>
     */
    abstract protected function getAutocompleteColumns(): array;

    public function autocomplete(): JsonResource
    {
        $request = $this->applyRequest();
        $term = $this->getAutocompleteTerm($request);

        if ($this->getAutocompleteColumns() !== [] && mb_strlen($term) < $this->getAutocompleteMinLength()) {
            return $this->makeAutocompleteCollection(new Collection());
        }

        return $this->makeAutocompleteCollection(
            $this->applyAutocompleteOrder($this->applyAutocompleteSearch($this->buildQuery($request), $term))
                ->limit($this->getAutocompleteLimit())
                ->get(),
        );
    }

    /**
     * @return class-string<JsonResource>
     */
    protected function getResourceAutocompleteClass(): string
    {
        return $this->resolvedResourceAutocompleteClass ??= $this->resolveResourceClass('AutocompleteResource')
            ?? AutocompleteResource::class;
    }

    /**
     * @return array<int, string>
     */
    protected function getAutocompleteSortableColumns(): array
    {
        return $this->getIndexSortableColumns();
    }

    protected function getAutocompleteLabelColumn(): string
    {
        return array_values($this->getAutocompleteColumns())[0]
            ?? throw new RuntimeException(sprintf('Autocomplete label column for [%s] is not defined.', static::class));
    }

    protected function getAutocompleteTerm(Request $request): string
    {
        $term = $request->input($this->getAutocompleteTermParameter());

        return is_string($term) ? $term : '';
    }

    protected function getAutocompleteTermParameter(): string
    {
        return (string) $this->getConfig('autocomplete.term_parameter');
    }

    protected function getAutocompleteLimit(): int
    {
        return max(1, (int) $this->getConfig('autocomplete.limit'));
    }

    protected function getAutocompleteMinLength(): int
    {
        return (int) $this->getConfig('autocomplete.min_length');
    }

    protected function applyAutocompleteSearch(Builder $query, string $term): Builder
    {
        $columns = $this->getAutocompleteColumns();

        if ($term === '' || $columns === []) {
            return $query;
        }

        $grammar = $query->getQuery()->getGrammar();
        $pattern = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term) . '%';

        return QueryHelper::groupWheres($query)->where(static function (Builder $query) use ($columns, $grammar, $pattern): void {
            foreach ($columns as $column) {
                $query->orWhereRaw(
                    sprintf("%s like ? escape '!'", $grammar->wrap($query->qualifyColumn($column))),
                    [$pattern],
                );
            }
        });
    }

    protected function applyAutocompleteOrder(Builder $query): Builder
    {
        if (QueryHelper::isSorted($query)) {
            return $query;
        }

        if ($this->getAutocompleteColumns() !== []) {
            $query->orderBy($query->qualifyColumn($this->getAutocompleteLabelColumn()));
        }

        $keyName = $query->getModel()->getKeyName();

        return $keyName === null ? $query : $query->orderBy($query->qualifyColumn($keyName));
    }

    private function makeAutocompleteCollection(Collection $models): JsonResource
    {
        $resource = $this->getResourceAutocompleteClass();

        if (is_a($resource, AutocompleteResource::class, true)) {
            $labelColumn = $this->getAutocompleteLabelColumn();
            $models = $models->map(static fn (Model $model): AutocompleteResource => new $resource($model, $labelColumn));
        }

        return $resource::collection($models);
    }
}
