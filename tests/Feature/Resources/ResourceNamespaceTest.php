<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature\Resources;

use JG\LaravelAutomaticCrud\Tests\Feature\CrudTestCase;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\ResourceNamespace\ItemController as ResourceNamespaceItemController;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use JG\LaravelAutomaticCrud\Tests\Support\Resources\ItemResource;
use PHPUnit\Framework\Attributes\Test;

final class ResourceNamespaceTest extends CrudTestCase
{
    protected function defineRoutes($router): void
    {
        $router->get('/resource-ns/items', [ResourceNamespaceItemController::class, 'index']);
    }

    #[Test]
    public function resource_namespace_method_overrides_config(): void
    {
        config(['automatic-crud.configs.default.namespaces.resource' => 'Wrong\Resources']);

        $item = Item::factory()->create();

        $this->getJson('/resource-ns/items')
            ->assertOk()
            ->assertJsonPath('data.0', (new ItemResource($item))->resolve());
    }
}
