<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature\Controllers\Index;

use JG\LaravelAutomaticCrud\Tests\Feature\CrudTestCase;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\ItemController;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\Pagination\PaginationOffController;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\Pagination\PaginationOnController;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\Pagination\PaginationOverrideOffController;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class PaginationTest extends CrudTestCase
{
    protected function defineRoutes($router): void
    {
        $router->get('/items', [ItemController::class, 'index']);
        $router->get('/items-pagination-off', [PaginationOffController::class, 'index']);
        $router->get('/items-pagination-on', [PaginationOnController::class, 'index']);
        $router->get('/items-pagination-override-off', [PaginationOverrideOffController::class, 'index']);
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
        config(['automatic-crud.configs.default.pagination.paginate' => false]);

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
        config(['automatic-crud.configs.default.pagination.paginate' => false]);

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
        config(['automatic-crud.configs.default.pagination.paginate' => false]);

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
        config(['automatic-crud.configs.default.pagination.paginate' => false]);

        Item::factory()->count(15)->create();

        $this->getJson('/items-pagination-on?pagination=false')
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonMissingPath('meta.per_page');
    }

    #[Test]
    public function paginates_correctly_with_page_parameter(): void
    {
        Item::factory()->count(15)->create();

        config(['automatic-crud.configs.default.pagination.per_page' => 10]);

        $this->getJson('/items?page=2')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.current_page', 2);
    }

    #[Test]
    public function page_zero_is_clamped_to_first_page(): void
    {
        Item::factory()->count(15)->create();

        $this->getJson('/items?page=0')
            ->assertOk()
            ->assertJsonPath('meta.current_page', 1);
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
                'automatic-crud.configs.default.pagination.paginate' => true,
                'automatic-crud.configs.default.pagination.allow_pagination_override' => false,
            ],
            '/items?pagination=false',
        ];
        yield 'by method' => [
            [],
            '/items-pagination-override-off?pagination=false',
        ];
    }
}
