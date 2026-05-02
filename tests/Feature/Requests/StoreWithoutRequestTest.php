<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature\Requests;

use Illuminate\Testing\TestResponse;
use JG\LaravelAutomaticCrud\Tests\Feature\CrudTestCase;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\SimpleItemController;
use PHPUnit\Framework\Attributes\Test;

final class StoreWithoutRequestTest extends CrudTestCase
{
    protected function defineRoutes($router): void
    {
        $router->apiResource('simple-items', SimpleItemController::class);
    }

    private function sendRequest(array $data = []): TestResponse
    {
        return $this->postJson(route('simple-items.store'), $data);
    }

    #[Test]
    public function creates_item_and_returns_201(): void
    {
        $this->sendRequest(['name' => 'New Item'])
            ->assertCreated();

        $this->assertDatabaseHas('items', ['name' => 'New Item']);
    }

    #[Test]
    public function stores_all_request_fields_without_filtering(): void
    {
        $this->sendRequest(['name' => 'New Item', 'secret' => 'sensitive'])
            ->assertCreated();

        $this->assertDatabaseHas('items', ['name' => 'New Item', 'secret' => 'sensitive']);
    }
}
