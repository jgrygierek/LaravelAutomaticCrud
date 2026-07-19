<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature\Controllers;

use JG\LaravelAutomaticCrud\Tests\Feature\CrudTestCase;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\SlugItemController;
use JG\LaravelAutomaticCrud\Tests\Support\Models\SlugItem;
use PHPUnit\Framework\Attributes\Test;

final class CustomRouteKeyTest extends CrudTestCase
{
    protected function defineRoutes($router): void
    {
        $router->apiResource('slug-items', SlugItemController::class);
    }

    #[Test]
    public function shows_item_by_custom_route_key(): void
    {
        $item = SlugItem::create(['name' => 'unique-name']);

        $this->getJson(route('slug-items.show', $item))
            ->assertOk()
            ->assertJsonPath('data.name', 'unique-name');
    }

    #[Test]
    public function returns_404_when_custom_route_key_does_not_match(): void
    {
        SlugItem::create(['name' => 'unique-name']);

        $this->getJson('/slug-items/missing-name')
            ->assertNotFound();
    }

    #[Test]
    public function updates_item_found_by_custom_route_key(): void
    {
        $item = SlugItem::create(['name' => 'unique-name']);

        $this->putJson(route('slug-items.update', $item), ['name' => 'updated-name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'updated-name');

        $this->assertDatabaseHas('items', ['id' => $item->id, 'name' => 'updated-name']);
    }

    #[Test]
    public function deletes_item_found_by_custom_route_key(): void
    {
        $item = SlugItem::create(['name' => 'unique-name']);

        $this->deleteJson(route('slug-items.destroy', $item))
            ->assertNoContent();

        $this->assertDatabaseMissing('items', ['id' => $item->id]);
    }
}
