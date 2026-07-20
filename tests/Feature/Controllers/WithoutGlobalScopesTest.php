<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature\Controllers;

use JG\LaravelAutomaticCrud\Tests\Feature\CrudTestCase;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\DiscardsModifyQueryParamController;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\WithoutGlobalScopesController;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use PHPUnit\Framework\Attributes\Test;

final class WithoutGlobalScopesTest extends CrudTestCase
{
    protected function defineRoutes($router): void
    {
        $router->apiResource('scoped-items', WithoutGlobalScopesController::class)
            ->only(['index', 'show', 'update', 'destroy']);

        $router->apiResource('discards-modify-query-items', DiscardsModifyQueryParamController::class)
            ->only(['index', 'show']);
    }

    #[Test]
    public function show_finds_item_excluded_by_global_scope(): void
    {
        $item = Item::factory()->create(['secret' => 'value']);

        $this->getJson(route('scoped-items.show', $item))
            ->assertOk()
            ->assertJsonPath('data.id', $item->id);
    }

    #[Test]
    public function update_finds_item_excluded_by_global_scope(): void
    {
        $item = Item::factory()->create(['secret' => 'value']);

        $this->putJson(route('scoped-items.update', $item), ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('items', ['id' => $item->id, 'name' => 'New Name']);
    }

    #[Test]
    public function destroy_finds_item_excluded_by_global_scope(): void
    {
        $item = Item::factory()->create(['secret' => 'value']);

        $this->deleteJson(route('scoped-items.destroy', $item))
            ->assertNoContent();

        $this->assertDatabaseMissing('items', ['id' => $item->id]);
    }

    #[Test]
    public function index_includes_item_excluded_by_global_scope(): void
    {
        $item = Item::factory()->create(['secret' => 'value']);

        $this->getJson('/scoped-items?pagination=false')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $item->id);
    }

    #[Test]
    public function show_finds_item_when_modify_query_override_discards_the_query_parameter(): void
    {
        $item = Item::factory()->create(['secret' => 'value']);

        $this->getJson(route('discards-modify-query-items.show', $item))
            ->assertOk()
            ->assertJsonPath('data.id', $item->id);
    }

    #[Test]
    public function index_includes_item_when_modify_query_override_discards_the_query_parameter(): void
    {
        $item = Item::factory()->create(['secret' => 'value']);

        $this->getJson('/discards-modify-query-items?pagination=false')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $item->id);
    }
}
