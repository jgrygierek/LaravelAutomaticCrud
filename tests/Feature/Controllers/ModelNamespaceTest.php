<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature\Controllers;

use JG\LaravelAutomaticCrud\Tests\Feature\CrudTestCase;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\ModelNamespace\ItemController as ModelNamespaceItemController;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use PHPUnit\Framework\Attributes\Test;

final class ModelNamespaceTest extends CrudTestCase
{
    protected function defineRoutes($router): void
    {
        $router->get('/model-ns/items', [ModelNamespaceItemController::class, 'index']);
        $router->post('/model-ns/items', [ModelNamespaceItemController::class, 'store']);
        $router->get('/model-ns/items/{id}', [ModelNamespaceItemController::class, 'show']);
        $router->put('/model-ns/items/{id}', [ModelNamespaceItemController::class, 'update']);
        $router->delete('/model-ns/items/{id}', [ModelNamespaceItemController::class, 'destroy']);
    }

    #[Test]
    public function model_namespace_method_overrides_config_for_index(): void
    {
        config(['automatic-crud.defaults.namespaces.model' => 'Wrong\Models']);

        Item::factory()->count(2)->create();

        $this->getJson('/model-ns/items')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function model_namespace_method_overrides_config_for_store(): void
    {
        config(['automatic-crud.defaults.namespaces.model' => 'Wrong\Models']);

        $this->postJson('/model-ns/items', ['name' => 'New Item'])
            ->assertCreated();

        $this->assertDatabaseHas('items', ['name' => 'New Item']);
    }

    #[Test]
    public function model_namespace_method_overrides_config_for_show(): void
    {
        config(['automatic-crud.defaults.namespaces.model' => 'Wrong\Models']);

        $item = Item::factory()->create();

        $this->getJson("/model-ns/items/{$item->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $item->id);
    }

    #[Test]
    public function model_namespace_method_overrides_config_for_update(): void
    {
        config(['automatic-crud.defaults.namespaces.model' => 'Wrong\Models']);

        $item = Item::factory()->create(['name' => 'Old']);

        $this->putJson("/model-ns/items/{$item->id}", ['name' => 'Updated'])
            ->assertOk();

        $this->assertDatabaseHas('items', ['id' => $item->id, 'name' => 'Updated']);
    }

    #[Test]
    public function model_namespace_method_overrides_config_for_destroy(): void
    {
        config(['automatic-crud.defaults.namespaces.model' => 'Wrong\Models']);

        $item = Item::factory()->create();

        $this->deleteJson("/model-ns/items/{$item->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('items', ['id' => $item->id]);
    }
}
