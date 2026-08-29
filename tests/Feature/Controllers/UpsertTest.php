<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature\Controllers;

use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use JG\LaravelAutomaticCrud\Tests\Feature\CrudTestCase;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\ItemController;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\SlugItemController;
use JG\LaravelAutomaticCrud\Tests\Support\Events\ItemCreatedEvent;
use JG\LaravelAutomaticCrud\Tests\Support\Events\ItemUpdatedEvent;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use JG\LaravelAutomaticCrud\Tests\Support\Resources\ItemResource;
use PHPUnit\Framework\Attributes\Test;

final class UpsertTest extends CrudTestCase
{
    protected function defineRoutes($router): void
    {
        $router->put('items/{item}', [ItemController::class, 'upsert'])->name('items.upsert');
        $router->put('slug-items/{item}', [SlugItemController::class, 'upsert'])->name('slug-items.upsert');
    }

    private function sendRequest(Item|int $item, array $data = []): TestResponse
    {
        return $this->putJson(route('items.upsert', $item), $data);
    }

    #[Test]
    public function updates_existing_item_and_returns_200(): void
    {
        $item = Item::factory()->create(['name' => 'Old Name']);

        $this->sendRequest($item, ['name' => 'New Name'])
            ->assertOk()
            ->assertExactJson(['data' => new ItemResource($item->fresh())->resolve()]);

        $this->assertSame('New Name', $item->fresh()->name);
    }

    #[Test]
    public function dispatches_updated_event_for_existing_item(): void
    {
        $item = Item::factory()->create();
        Event::fake();

        $this->sendRequest($item, ['name' => 'New Name'])->assertOk();

        Event::assertDispatched(ItemUpdatedEvent::class, fn (ItemUpdatedEvent $event): bool => $event->model->is($item));
    }

    #[Test]
    public function creates_item_by_route_key_when_missing_and_returns_201(): void
    {
        $this->putJson('/slug-items/new-name', ['secret' => 'value'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'new-name');

        $this->assertDatabaseHas('items', ['name' => 'new-name', 'secret' => 'value']);
    }

    #[Test]
    public function dispatches_created_event_when_item_is_missing(): void
    {
        Event::fake();

        $this->sendRequest(999, ['name' => 'Brand New'])->assertCreated();

        Event::assertDispatched(ItemCreatedEvent::class, fn (ItemCreatedEvent $event): bool => $event->model->name === 'Brand New');
    }

    #[Test]
    public function returns_422_when_form_request_validation_fails(): void
    {
        $item = Item::factory()->create();

        $this->sendRequest($item)
            ->assertUnprocessable()
            ->assertInvalid(['name' => 'The name field is required.']);
    }
}
