<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature\Controllers;

use JG\LaravelAutomaticCrud\Tests\Feature\CrudTestCase;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\ModifyQueryController;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use PHPUnit\Framework\Attributes\Test;

final class ModifyQueryScopeTest extends CrudTestCase
{
    protected function defineRoutes($router): void
    {
        $router->apiResource('modify-query-items', ModifyQueryController::class)->only(['show', 'update', 'destroy']);
    }

    #[Test]
    public function show_returns_404_for_item_excluded_by_modify_query(): void
    {
        $item = Item::factory()->create(['secret' => 'value']);

        $this->getJson(route('modify-query-items.show', $item))
            ->assertNotFound();
    }

    #[Test]
    public function update_returns_404_for_item_excluded_by_modify_query(): void
    {
        $item = Item::factory()->create(['secret' => 'value']);

        $this->putJson(route('modify-query-items.update', $item), ['name' => 'New Name'])
            ->assertNotFound();
    }

    #[Test]
    public function destroy_returns_404_for_item_excluded_by_modify_query(): void
    {
        $item = Item::factory()->create(['secret' => 'value']);

        $this->deleteJson(route('modify-query-items.destroy', $item))
            ->assertNotFound();
    }
}
