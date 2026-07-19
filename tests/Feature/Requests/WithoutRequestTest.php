<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature\Requests;

use JG\LaravelAutomaticCrud\Tests\Feature\CrudTestCase;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\SimpleItemController;
use JG\LaravelAutomaticCrud\Tests\Support\Models\SimpleItem;
use PHPUnit\Framework\Attributes\Test;

final class WithoutRequestTest extends CrudTestCase
{
    protected function defineRoutes($router): void
    {
        $router->apiResource('simple-items', SimpleItemController::class);
    }

    #[Test]
    public function show_returns_item(): void
    {
        $item = SimpleItem::create(['name' => 'Test Item']);

        $this->getJson(route('simple-items.show', $item))
            ->assertOk()
            ->assertJsonPath('data.id', $item->id)
            ->assertJsonPath('data.name', $item->name);
    }

    #[Test]
    public function show_returns_404_for_missing_item(): void
    {
        $this->getJson(route('simple-items.show', 999))
            ->assertNotFound();
    }

    #[Test]
    public function store_creates_item_and_returns_201(): void
    {
        $this->postJson(route('simple-items.store'), ['name' => 'New Item'])
            ->assertCreated();

        $this->assertDatabaseHas('items', ['name' => 'New Item']);
    }

    #[Test]
    public function store_stores_all_request_fields_without_filtering(): void
    {
        $this->postJson(route('simple-items.store'), ['name' => 'New Item', 'secret' => 'sensitive'])
            ->assertCreated();

        $this->assertDatabaseHas('items', ['name' => 'New Item', 'secret' => 'sensitive']);
    }

    #[Test]
    public function update_modifies_item(): void
    {
        $item = SimpleItem::create(['name' => 'Old Name']);

        $this->putJson(route('simple-items.update', $item), ['name' => 'New Name', 'secret' => 'sensitive'])
            ->assertOk();

        $this->assertDatabaseHas('items', ['id' => $item->id, 'name' => 'New Name', 'secret' => 'sensitive']);
    }

    #[Test]
    public function update_returns_404_for_missing_item(): void
    {
        $this->putJson(route('simple-items.update', 999), ['name' => 'New Name'])
            ->assertNotFound();
    }

    #[Test]
    public function destroy_deletes_item_and_returns_204(): void
    {
        $item = SimpleItem::create(['name' => 'Test Item']);

        $this->deleteJson(route('simple-items.destroy', $item))
            ->assertNoContent();

        $this->assertDatabaseMissing('items', ['id' => $item->id]);
    }

    #[Test]
    public function destroy_returns_404_for_missing_item(): void
    {
        $this->deleteJson(route('simple-items.destroy', 999))
            ->assertNotFound();
    }
}
