<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature\Resources;

use JG\LaravelAutomaticCrud\Tests\Feature\CrudTestCase;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\ResourceClassOverrideController;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\WithCollectionController;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use PHPUnit\Framework\Attributes\Test;

final class ResourceCollectionClassTest extends CrudTestCase
{
    protected function defineRoutes($router): void
    {
        $router->get('/collection-items', [WithCollectionController::class, 'index']);
        $router->get('/collection-items/{item}', [WithCollectionController::class, 'show']);
        $router->get('/override-items', [ResourceClassOverrideController::class, 'index']);
        $router->get('/override-items/{item}', [ResourceClassOverrideController::class, 'show']);
    }

    #[Test]
    public function index_uses_collection_resource_class_when_it_exists(): void
    {
        Item::factory()->create(['name' => 'Test']);

        $this->getJson('/collection-items?pagination=false')
            ->assertOk()
            ->assertJsonPath('data.0.type', 'collection');
    }

    #[Test]
    public function show_uses_resource_class_not_collection_resource_class(): void
    {
        $item = Item::factory()->create(['name' => 'Test']);

        $this->getJson('/collection-items/' . $item->id)
            ->assertOk()
            ->assertJsonMissingPath('data.type');
    }

    #[Test]
    public function index_falls_back_to_overridden_resource_class_when_no_collection_resource_exists(): void
    {
        Item::factory()->create(['name' => 'Test']);

        $this->getJson('/override-items?pagination=false')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'created_at']]]);
    }

    #[Test]
    public function show_uses_overridden_resource_class(): void
    {
        $item = Item::factory()->create(['name' => 'Test']);

        $this->getJson('/override-items/' . $item->id)
            ->assertOk()
            ->assertJsonStructure(['data' => ['id', 'name', 'created_at']]);
    }
}
