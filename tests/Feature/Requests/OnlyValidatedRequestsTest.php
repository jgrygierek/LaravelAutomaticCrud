<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature\Requests;

use JG\LaravelAutomaticCrud\Tests\Feature\CrudTestCase;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\ItemController;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use PHPUnit\Framework\Attributes\Test;

final class OnlyValidatedRequestsTest extends CrudTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('automatic-crud.configs.default.requests.only_validated', true);
        config()->set('automatic-crud.configs.default.requests.force_custom', true);
    }

    protected function defineRoutes($router): void
    {
        $router->apiResource('items', ItemController::class);
    }

    #[Test]
    public function store_strips_fields_not_in_validation_rules(): void
    {
        $this->postJson(route('items.store'), ['name' => 'Test', 'secret' => 'sensitive'])
            ->assertCreated();

        $this->assertDatabaseMissing('items', ['secret' => 'sensitive']);
        $this->assertDatabaseHas('items', ['name' => 'Test', 'secret' => null]);
    }

    #[Test]
    public function update_strips_fields_not_in_validation_rules(): void
    {
        $item = Item::create(['name' => 'Old']);

        $this->putJson(route('items.update', $item), ['name' => 'New', 'secret' => 'sensitive'])
            ->assertOk();

        $this->assertDatabaseMissing('items', ['secret' => 'sensitive']);
        $this->assertDatabaseHas('items', ['id' => $item->id, 'name' => 'New', 'secret' => null]);
    }
}
