<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature\Controllers;

use JG\LaravelAutomaticCrud\Tests\Feature\CrudTestCase;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\CustomPerPageController;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\ItemController;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\PaginationOffController;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\PaginationOnController;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\PaginationOverrideOffController;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\PerPageOverrideOffController;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use JG\LaravelAutomaticCrud\Tests\Support\Resources\ItemResource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class IndexTest extends CrudTestCase
{
    protected function defineRoutes($router): void
    {
        $router->get('/items', [ItemController::class, 'index']);
        $router->get('/items-pagination-off', [PaginationOffController::class, 'index']);
        $router->get('/items-pagination-on', [PaginationOnController::class, 'index']);
        $router->get('/items-per-page-3', [CustomPerPageController::class, 'index']);
        $router->get('/items-pagination-override-off', [PaginationOverrideOffController::class, 'index']);
        $router->get('/items-per-page-override-off', [PerPageOverrideOffController::class, 'index']);
    }

    #[Test]
    public function returns_paginated_list(): void
    {
        $items = Item::factory()->count(3)->create();

        $response = $this->getJson('/items')->assertOk();

        $response->assertJsonCount(3, 'data');
        $this->assertEquals(
            $items->map(fn (Item $item) => new ItemResource($item)->resolve())->toArray(),
            $response->json('data'),
        );
    }

    #[Test]
    public function returns_empty_list(): void
    {
        $this->getJson('/items')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    #[Test]
    public function pagination_enabled_by_default_config(): void
    {
        Item::factory()->count(15)->create();

        $this->getJson('/items')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure(['data', 'meta', 'links'])
            ->assertJsonPath('meta.per_page', 10);
    }

    #[Test]
    public function pagination_disabled_by_config(): void
    {
        config(['automatic-crud.defaults.pagination.paginate' => false]);

        Item::factory()->count(15)->create();

        $this->getJson('/items')
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonMissingPath('meta.per_page');
    }

    #[Test]
    public function pagination_disabled_via_query_param_overrides_enabled_config(): void
    {
        Item::factory()->count(15)->create();

        $this->getJson('/items?pagination=false')
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonMissingPath('meta.per_page');
    }

    #[Test]
    public function pagination_enabled_via_query_param_overrides_disabled_config(): void
    {
        config(['automatic-crud.defaults.pagination.paginate' => false]);

        Item::factory()->count(15)->create();

        $this->getJson('/items?pagination=true')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure(['data', 'meta', 'links']);
    }

    #[Test]
    public function pagination_disabled_by_method_overrides_enabled_config(): void
    {
        Item::factory()->count(15)->create();

        $this->getJson('/items-pagination-off')
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonMissingPath('meta.per_page');
    }

    #[Test]
    public function pagination_enabled_by_method_overrides_disabled_config(): void
    {
        config(['automatic-crud.defaults.pagination.paginate' => false]);

        Item::factory()->count(15)->create();

        $this->getJson('/items-pagination-on')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure(['data', 'meta', 'links']);
    }

    #[Test]
    public function pagination_query_param_overrides_method_when_method_returns_false(): void
    {
        Item::factory()->count(15)->create();

        $this->getJson('/items-pagination-off?pagination=true')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure(['data', 'meta', 'links']);
    }

    #[Test]
    public function pagination_query_param_overrides_method_when_method_returns_true(): void
    {
        config(['automatic-crud.defaults.pagination.paginate' => false]);

        Item::factory()->count(15)->create();

        $this->getJson('/items-pagination-on?pagination=false')
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonMissingPath('meta.per_page');
    }

    #[Test]
    public function per_page_uses_config_value(): void
    {
        config(['automatic-crud.defaults.pagination.per_page' => 5]);

        Item::factory()->count(10)->create();

        $this->getJson('/items')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.per_page', 5);
    }

    #[Test]
    public function per_page_query_param_overrides_config(): void
    {
        Item::factory()->count(20)->create();

        $this->getJson('/items?per_page=4')
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('meta.per_page', 4);
    }

    #[Test]
    public function per_page_zero_is_clamped_to_one(): void
    {
        Item::factory()->count(5)->create();

        $this->getJson('/items?per_page=0')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.per_page', 1);
    }

    #[Test]
    public function per_page_negative_value_is_clamped_to_one(): void
    {
        Item::factory()->count(5)->create();

        $this->getJson('/items?per_page=-10')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.per_page', 1);
    }

    #[Test]
    public function per_page_method_overrides_config(): void
    {
        Item::factory()->count(10)->create();

        $this->getJson('/items-per-page-3')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.per_page', 3);
    }

    #[Test]
    public function per_page_query_param_overrides_method(): void
    {
        Item::factory()->count(10)->create();

        $this->getJson('/items-per-page-3?per_page=6')
            ->assertOk()
            ->assertJsonCount(6, 'data')
            ->assertJsonPath('meta.per_page', 6);
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
            '/items?sort_by=name&sort_direction=asc&pagination=false',
            ['Alpha', 'Beta', 'Gamma'],
        ];
        yield 'descending explicitly' => [
            '/items?sort_by=name&sort_direction=desc&pagination=false',
            ['Gamma', 'Beta', 'Alpha'],
        ];
        yield 'ascending by default' => [
            '/items?sort_by=name&pagination=false',
            ['Alpha', 'Beta', 'Gamma'],
        ];
    }

    #[Test]
    public function paginates_correctly_with_page_parameter(): void
    {
        Item::factory()->count(15)->create();

        config(['automatic-crud.defaults.pagination.per_page' => 10]);

        $this->getJson('/items?page=2')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.current_page', 2);
    }

    #[Test]
    #[DataProvider('paginationOverrideDisabledProvider')]
    public function pagination_query_param_ignored_when_override_disabled(array $configOverrides, string $url): void
    {
        config($configOverrides);

        Item::factory()->count(15)->create();

        $this->getJson($url)
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.per_page', 10);
    }

    public static function paginationOverrideDisabledProvider(): iterable
    {
        yield 'by config' => [
            [
                'automatic-crud.defaults.pagination.paginate' => true,
                'automatic-crud.defaults.pagination.allow_pagination_override' => false,
            ],
            '/items?pagination=false',
        ];
        yield 'by method' => [
            [],
            '/items-pagination-override-off?pagination=false',
        ];
    }

    #[Test]
    #[DataProvider('perPageOverrideDisabledProvider')]
    public function per_page_query_param_ignored_when_override_disabled(array $configOverrides, string $url): void
    {
        config($configOverrides);

        Item::factory()->count(15)->create();

        $this->getJson($url)
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.per_page', 10);
    }

    public static function perPageOverrideDisabledProvider(): iterable
    {
        yield 'by config' => [
            [
                'automatic-crud.defaults.pagination.per_page' => 10,
                'automatic-crud.defaults.pagination.allow_per_page_override' => false,
            ],
            '/items?per_page=3',
        ];
        yield 'by method' => [
            [
                'automatic-crud.defaults.pagination.per_page' => 10,
            ],
            '/items-per-page-override-off?per_page=3',
        ];
    }
}
