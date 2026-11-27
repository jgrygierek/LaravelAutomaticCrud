<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature\Controllers\Index;

use Illuminate\Database\Eloquent\Builder;
use JG\LaravelAutomaticCrud\Tests\Feature\CrudTestCase;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\ItemController;
use JG\LaravelAutomaticCrud\Tests\Support\Models\HiddenSecretItem;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use JG\LaravelAutomaticCrud\Tests\Support\Models\VisibleNameItem;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

final class SortingTest extends CrudTestCase
{
    private function useModel(string $modelClass): void
    {
        $this->app->instance(ItemController::class, new class($modelClass) extends ItemController
        {
            public function __construct(private readonly string $modelClass) {}

            protected function getModelClass(): string
            {
                return $this->modelClass;
            }
        });
    }

    #[Test]
    #[DataProvider('sortingProvider')]
    public function sorts_results(string $url, array $expectedNames): void
    {
        Item::factory()->create(['name' => 'Beta']);
        Item::factory()->create(['name' => 'Alpha']);
        Item::factory()->create(['name' => 'Gamma']);

        $names = collect(
            $this->getJson($url)->assertOk()->json('data'),
        )->pluck('name')->toArray();

        $this->assertSame($expectedNames, $names);
    }

    public static function sortingProvider(): iterable
    {
        yield 'ascending explicitly' => [
            '/items?sort_by=name&sort_direction=asc',
            ['Alpha', 'Beta', 'Gamma'],
        ];
        yield 'descending explicitly' => [
            '/items?sort_by=name&sort_direction=desc',
            ['Gamma', 'Beta', 'Alpha'],
        ];
        yield 'ascending by default' => [
            '/items?sort_by=name',
            ['Alpha', 'Beta', 'Gamma'],
        ];
        yield 'descending case-insensitively' => [
            '/items?sort_by=name&sort_direction=DESC',
            ['Gamma', 'Beta', 'Alpha'],
        ];
        yield 'unsorted when only direction is given' => [
            '/items?sort_direction=sideways',
            ['Beta', 'Alpha', 'Gamma'],
        ];
    }

    #[Test]
    public function sorts_by_column_added_to_default_sortable_columns(): void
    {
        $this->app->instance(ItemController::class, new class extends ItemController
        {
            protected function getModelClass(): string
            {
                return Item::class;
            }

            protected function modifyQuery(Builder $query): Builder
            {
                return $query->select('items.*')->selectRaw('length(name) as name_length');
            }

            protected function getIndexSortableColumns(): array
            {
                return [...$this->defaultSortableColumns(), 'name_length'];
            }
        });
        Item::factory()->create(['name' => 'Ccc']);
        Item::factory()->create(['name' => 'A']);
        Item::factory()->create(['name' => 'Bb']);

        $this->getJson('/items?sort_by=name_length')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'A')
            ->assertJsonPath('data.1.name', 'Bb')
            ->assertJsonPath('data.2.name', 'Ccc');
    }

    #[Test]
    public function sorts_by_visible_column_of_model_with_visible_columns(): void
    {
        $this->useModel(VisibleNameItem::class);
        Item::factory()->create(['name' => 'Beta']);
        Item::factory()->create(['name' => 'Alpha']);

        $this->getJson('/items?sort_by=name')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Alpha')
            ->assertJsonPath('data.1.name', 'Beta');
    }

    #[Test]
    #[DataProvider('invalidSortingProvider')]
    public function returns_422_when_sorting_parameter_is_invalid(string $query, string $invalidField): void
    {
        $this->getJson('/items?' . $query)
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors($invalidField);
    }

    public static function invalidSortingProvider(): iterable
    {
        yield 'unknown direction' => ['sort_by=name&sort_direction=sideways', 'sort_direction'];
        yield 'direction as array' => ['sort_by=name&sort_direction[]=desc', 'sort_direction'];
        yield 'column as array' => ['sort_by[]=name', 'sort_by'];
    }

    #[Test]
    #[TestWith(['secret'])]
    #[TestWith(['label'])]
    public function returns_422_when_sorting_by_column_outside_visible_table_columns(string $column): void
    {
        $this->useModel(VisibleNameItem::class);

        $this->getJson('/items?sort_by=' . $column)
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors('sort_by');
    }

    #[Test]
    public function returns_422_when_sorting_by_unknown_column(): void
    {
        $this->getJson('/items?sort_by=missing')
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors('sort_by');
    }

    #[Test]
    public function returns_422_when_sorting_by_hidden_column(): void
    {
        $this->useModel(HiddenSecretItem::class);

        $this->getJson('/items?sort_by=secret')
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors('sort_by');
    }

    #[Test]
    public function returns_422_when_sorting_by_column_outside_overridden_sortable_columns(): void
    {
        $this->app->instance(ItemController::class, new class extends ItemController
        {
            protected function getModelClass(): string
            {
                return Item::class;
            }

            protected function getIndexSortableColumns(): array
            {
                return ['name'];
            }
        });

        $this->getJson('/items?sort_by=created_at')
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors('sort_by');
    }
}
