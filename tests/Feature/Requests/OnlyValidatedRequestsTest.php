<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature\Requests;

use JG\LaravelAutomaticCrud\Tests\Feature\CrudTestCase;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\ItemController;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\SimpleItemController;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use JG\LaravelAutomaticCrud\Tests\Support\Models\SimpleItem;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

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
        $router->apiResource('simple-items', SimpleItemController::class);
    }

    #[Test]
    public function throws_exception_on_store_when_request_class_does_not_exist(): void
    {
        $this->withoutExceptionHandling();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Request class \[.+\] not found\./');

        $this->postJson(route('simple-items.store'), ['name' => 'Test']);
    }

    #[Test]
    public function throws_exception_on_show_when_request_class_does_not_exist(): void
    {
        $this->withoutExceptionHandling();

        $item = SimpleItem::create(['name' => 'Test']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Request class \[.+\] not found\./');

        $this->getJson(route('simple-items.show', $item));
    }

    #[Test]
    public function throws_exception_on_update_when_request_class_does_not_exist(): void
    {
        $this->withoutExceptionHandling();

        $item = SimpleItem::create(['name' => 'Test']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Request class \[.+\] not found\./');

        $this->putJson(route('simple-items.update', $item), ['name' => 'Updated']);
    }

    #[Test]
    public function throws_exception_on_destroy_when_request_class_does_not_exist(): void
    {
        $this->withoutExceptionHandling();

        $item = SimpleItem::create(['name' => 'Test']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Request class \[.+\] not found\./');

        $this->deleteJson(route('simple-items.destroy', $item));
    }

    #[Test]
    public function throws_exception_on_index_when_request_class_does_not_exist(): void
    {
        $this->withoutExceptionHandling();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Request class \[.+\] not found\./');

        $this->getJson(route('simple-items.index'));
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
