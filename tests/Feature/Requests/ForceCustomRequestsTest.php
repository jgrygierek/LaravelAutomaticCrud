<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature\Requests;

use JG\LaravelAutomaticCrud\Tests\Feature\CrudTestCase;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\SimpleItemController;
use JG\LaravelAutomaticCrud\Tests\Support\Models\SimpleItem;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

final class ForceCustomRequestsTest extends CrudTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('automatic-crud.defaults.requests.force_custom', true);
    }

    protected function defineRoutes($router): void
    {
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
}
