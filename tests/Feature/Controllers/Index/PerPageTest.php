<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature\Controllers\Index;

use JG\LaravelAutomaticCrud\Tests\Feature\CrudTestCase;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\CustomPerPageController;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\ItemController;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\Pagination\PerPageOverrideOffController;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class PerPageTest extends CrudTestCase
{
    protected function defineRoutes($router): void
    {
        $router->get('/items', [ItemController::class, 'index']);
        $router->get('/items-per-page-3', [CustomPerPageController::class, 'index']);
        $router->get('/items-per-page-override-off', [PerPageOverrideOffController::class, 'index']);
    }

    #[Test]
    public function per_page_uses_config_value(): void
    {
        config(['automatic-crud.configs.default.pagination.per_page' => 5]);

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
                'automatic-crud.configs.default.pagination.per_page' => 10,
                'automatic-crud.configs.default.pagination.allow_per_page_override' => false,
            ],
            '/items?per_page=3',
        ];
        yield 'by method' => [
            [
                'automatic-crud.configs.default.pagination.per_page' => 10,
            ],
            '/items-per-page-override-off?per_page=3',
        ];
    }
}
