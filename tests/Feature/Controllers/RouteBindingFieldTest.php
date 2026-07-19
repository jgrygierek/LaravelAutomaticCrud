<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature\Controllers;

use JG\LaravelAutomaticCrud\Tests\Feature\CrudTestCase;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\ItemController;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use PHPUnit\Framework\Attributes\Test;

final class RouteBindingFieldTest extends CrudTestCase
{
    protected function defineRoutes($router): void
    {
        $router->get('binding-field-items/{item:name}', [ItemController::class, 'show']);
        $router->put('binding-field-items/{item:name}', [ItemController::class, 'update']);
        $router->delete('binding-field-items/{item:name}', [ItemController::class, 'destroy']);
        $router->get('plain-items/{item}', [ItemController::class, 'show']);
    }

    #[Test]
    public function shows_item_by_route_binding_field_without_changing_model_route_key(): void
    {
        $item = Item::factory()->create(['name' => 'unique-name']);

        $this->getJson("/binding-field-items/{$item->name}")
            ->assertOk()
            ->assertJsonPath('data.id', $item->id);
    }

    #[Test]
    public function returns_404_when_route_binding_field_does_not_match(): void
    {
        Item::factory()->create(['name' => 'unique-name']);

        $this->getJson('/binding-field-items/missing-name')
            ->assertNotFound();
    }

    #[Test]
    public function updates_item_found_by_route_binding_field(): void
    {
        $item = Item::factory()->create(['name' => 'unique-name']);

        $this->putJson("/binding-field-items/{$item->name}", ['name' => 'updated-name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'updated-name');
    }

    #[Test]
    public function deletes_item_found_by_route_binding_field(): void
    {
        $item = Item::factory()->create(['name' => 'unique-name']);

        $this->deleteJson("/binding-field-items/{$item->name}")
            ->assertNoContent();

        $this->assertDatabaseMissing('items', ['id' => $item->id]);
    }

    #[Test]
    public function falls_back_to_primary_key_when_route_has_no_binding_field(): void
    {
        $item = Item::factory()->create(['name' => 'Test Item']);

        $this->getJson("/plain-items/{$item->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $item->id);
    }
}
