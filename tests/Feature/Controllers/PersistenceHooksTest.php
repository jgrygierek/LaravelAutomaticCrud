<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature\Controllers;

use JG\LaravelAutomaticCrud\Tests\Feature\CrudTestCase;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\PersistenceHooksController;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use PHPUnit\Framework\Attributes\Test;

final class PersistenceHooksTest extends CrudTestCase
{
    protected function defineRoutes($router): void
    {
        $router->apiResource('persistence-hooks-items', PersistenceHooksController::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        PersistenceHooksController::$deleteModelCalls = 0;
    }

    #[Test]
    public function store_uses_overridden_create_model_hook(): void
    {
        $this->postJson(route('persistence-hooks-items.store'), ['name' => 'New Item'])
            ->assertCreated();

        $this->assertDatabaseHas('items', ['name' => 'New Item', 'secret' => 'created-hook']);
    }

    #[Test]
    public function update_uses_overridden_update_model_hook(): void
    {
        $item = Item::factory()->create(['secret' => null]);

        $this->putJson(route('persistence-hooks-items.update', $item), ['name' => 'Changed'])
            ->assertOk();

        $this->assertDatabaseHas('items', ['id' => $item->id, 'name' => 'Changed', 'secret' => 'updated-hook']);
    }

    #[Test]
    public function destroy_uses_overridden_delete_model_hook(): void
    {
        $item = Item::factory()->create();

        $this->deleteJson(route('persistence-hooks-items.destroy', $item))
            ->assertNoContent();

        $this->assertSame(1, PersistenceHooksController::$deleteModelCalls);
        $this->assertDatabaseMissing('items', ['id' => $item->id]);
    }
}
