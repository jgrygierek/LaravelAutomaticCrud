<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature\Controllers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;
use JG\LaravelAutomaticCrud\Tests\Feature\CrudTestCase;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\ItemController;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use PHPUnit\Framework\Attributes\Test;

final class NestedRouteTest extends CrudTestCase
{
    protected function defineRoutes($router): void
    {
        $router->get('parents/{parent}/items/{item}', [ItemController::class, 'show']);
        $router->put('parents/{parent}/items/{item}', [ItemController::class, 'update']);
        $router->delete('parents/{parent}/items/{item}', [ItemController::class, 'destroy']);
        $router->put('parents/{parent}/upsert-items/{item}', [ItemController::class, 'upsert']);
        $router->get('parents/{parent}/named-items/{item:name}', [ItemController::class, 'show']);
    }

    #[Test]
    public function shows_item_identified_by_last_route_parameter(): void
    {
        $item = Item::factory()->create();
        $parentLookalike = Item::factory()->create();

        $this->getJson("/parents/{$parentLookalike->id}/items/{$item->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $item->id);
    }

    #[Test]
    public function shows_item_identified_by_last_route_parameter_binding_field(): void
    {
        $item = Item::factory()->create(['name' => 'unique-name']);
        $parentLookalike = Item::factory()->create();

        $this->getJson("/parents/{$parentLookalike->id}/named-items/{$item->name}")
            ->assertOk()
            ->assertJsonPath('data.id', $item->id);
    }

    #[Test]
    public function updates_item_identified_by_last_route_parameter(): void
    {
        $item = Item::factory()->create(['name' => 'Old Name']);
        $parentLookalike = Item::factory()->create(['name' => 'Parent Name']);

        $this->putJson("/parents/{$parentLookalike->id}/items/{$item->id}", ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('data.id', $item->id)
            ->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('items', ['id' => $parentLookalike->id, 'name' => 'Parent Name']);
    }

    #[Test]
    public function deletes_item_identified_by_last_route_parameter(): void
    {
        $item = Item::factory()->create();
        $parentLookalike = Item::factory()->create();

        $this->deleteJson("/parents/{$parentLookalike->id}/items/{$item->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('items', ['id' => $item->id]);
        $this->assertDatabaseHas('items', ['id' => $parentLookalike->id]);
    }

    #[Test]
    public function upserts_item_identified_by_last_route_parameter(): void
    {
        $item = Item::factory()->create(['name' => 'Old Name']);
        $parentLookalike = Item::factory()->create(['name' => 'Parent Name']);

        $this->putJson("/parents/{$parentLookalike->id}/upsert-items/{$item->id}", ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('data.id', $item->id)
            ->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('items', ['id' => $parentLookalike->id, 'name' => 'Parent Name']);
    }

    #[Test]
    public function shows_item_passed_explicitly_when_action_is_called_from_another_route_with_parameters(): void
    {
        $item = Item::factory()->create();
        $parentLookalike = Item::factory()->create();

        Route::get('parents/{parent}/explicit-item', fn (): JsonResource => app(ItemController::class)->show($item->id));

        $this->getJson("/parents/{$parentLookalike->id}/explicit-item")
            ->assertOk()
            ->assertJsonPath('data.id', $item->id);
    }

    #[Test]
    public function returns_404_when_last_route_parameter_does_not_match_even_if_parent_does(): void
    {
        $parentLookalike = Item::factory()->create();

        $this->getJson("/parents/{$parentLookalike->id}/items/999")
            ->assertNotFound();
    }

    #[Test]
    public function returns_422_when_updating_item_from_nested_route_with_invalid_data(): void
    {
        $item = Item::factory()->create(['name' => 'Old Name']);
        $parentLookalike = Item::factory()->create();

        $this->putJson("/parents/{$parentLookalike->id}/items/{$item->id}", ['name' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);

        $this->assertDatabaseHas('items', ['id' => $item->id, 'name' => 'Old Name']);
    }
}
