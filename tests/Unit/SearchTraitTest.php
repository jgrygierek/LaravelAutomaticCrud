<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Unit;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use JG\LaravelAutomaticCrud\Tests\Support\Models\SearchableItem;
use JG\LaravelAutomaticCrud\Tests\TestCase;
use JG\LaravelAutomaticCrud\Traits\SearchTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class SearchTraitTest extends TestCase
{
    #[Test]
    public function build_query_skips_search_filters_for_non_searchable_model(): void
    {
        request()->merge(['name' => 'Alpha']);

        $model = new class extends Model {};
        $controller = $this->makeController(get_class($model));

        $this->assertEmpty($controller->exposeBuildQuery()->getQuery()->wheres);
    }

    #[Test]
    public function build_query_applies_search_filters_for_searchable_model(): void
    {
        request()->merge(['name' => 'Alpha']);

        $controller = $this->makeController(SearchableItem::class);
        $wheres = $controller->exposeBuildQuery()->getQuery()->wheres;

        $this->assertNotNull($wheres);
        $this->assertSame('name', $wheres[0]['column']);
        $this->assertSame('Alpha', $wheres[0]['value']);
    }

    #[Test]
    public function build_query_skips_sorting_when_no_sort_by_param(): void
    {
        $controller = $this->makeController(Item::class);

        $this->assertNull($controller->exposeBuildQuery()->getQuery()->orders);
    }

    #[Test]
    #[DataProvider('sortDirectionProvider')]
    public function build_query_applies_sorting_with_given_direction(array $params, string $expectedDirection): void
    {
        request()->merge($params);

        $controller = $this->makeController(Item::class);
        $orders = $controller->exposeBuildQuery()->getQuery()->orders;

        $this->assertCount(1, $orders);
        $this->assertSame('name', $orders[0]['column']);
        $this->assertSame($expectedDirection, $orders[0]['direction']);
    }

    public static function sortDirectionProvider(): iterable
    {
        yield 'ascending by default' => [['sort_by' => 'name'], 'asc'];
        yield 'ascending explicitly' => [['sort_by' => 'name', 'sort_direction' => 'asc'], 'asc'];
        yield 'descending explicitly' => [['sort_by' => 'name', 'sort_direction' => 'desc'], 'desc'];
    }

    #[Test]
    public function build_query_skips_sorting_when_query_already_sorted(): void
    {
        request()->merge(['sort_by' => 'name']);

        $controller = new class
        {
            use SearchTrait;

            public function getModelClass(): string
            {
                return Item::class;
            }

            protected function modifyQuery(Builder $query): Builder
            {
                return $query->orderBy('id', 'asc');
            }

            public function exposeBuildQuery(): Builder
            {
                return $this->buildQuery(request());
            }
        };

        $orders = $controller->exposeBuildQuery()->getQuery()->orders;

        $this->assertCount(1, $orders);
        $this->assertSame('id', $orders[0]['column']);
    }

    private function makeController(string $modelClass): object
    {
        return new class($modelClass)
        {
            use SearchTrait;

            public function __construct(private readonly string $model) {}

            public function getModelClass(): string
            {
                return $this->model;
            }

            public function exposeBuildQuery(): Builder
            {
                return $this->buildQuery(request());
            }
        };
    }
}
