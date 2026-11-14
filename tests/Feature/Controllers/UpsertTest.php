<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use JG\LaravelAutomaticCrud\Tests\Feature\CrudTestCase;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\ItemController;
use JG\LaravelAutomaticCrud\Tests\Support\Events\ItemCreatedEvent;
use JG\LaravelAutomaticCrud\Tests\Support\Events\ItemUpdatedEvent;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use JG\LaravelAutomaticCrud\Tests\Support\Resources\ItemResource;
use PHPUnit\Framework\Attributes\Test;

final class UpsertTest extends CrudTestCase
{
    protected function defineRoutes($router): void
    {
        $router->put('owners/{owner}/item', [ItemController::class, 'upsert'])->name('items.upsert');
        $router->put('item', [ItemController::class, 'upsert'])->name('item.upsert');
        $router->put('owners/{owner}/items/{name}', [ItemController::class, 'upsert'])->name('owners.items.upsert');
    }

    private function bindControllerIdentifyingItemsByOwnerAndName(): void
    {
        $this->app->instance(ItemController::class, new class extends ItemController
        {
            protected function getModelClass(): string
            {
                return Item::class;
            }

            protected function getUpsertAttributes(): array
            {
                return [
                    'secret' => request()->route('owner'),
                    'name' => request()->route('name'),
                ];
            }
        });
    }

    private function sendRequest(string $owner, array $data = []): TestResponse
    {
        return $this->putJson(route('items.upsert', $owner), $data);
    }

    #[Test]
    public function updates_existing_item_and_returns_200(): void
    {
        $item = Item::factory()->create(['name' => 'Old Name', 'secret' => 'owner-a']);
        $otherItem = Item::factory()->create(['name' => 'Other Name', 'secret' => 'owner-b']);

        $this->sendRequest('owner-a', ['name' => 'New Name'])
            ->assertOk()
            ->assertExactJson(['data' => new ItemResource($item->fresh())->resolve()]);

        $this->assertDatabaseCount('items', 2);
        $this->assertDatabaseHas('items', ['id' => $item->id, 'name' => 'New Name', 'secret' => 'owner-a']);
        $this->assertDatabaseHas('items', ['id' => $otherItem->id, 'name' => 'Other Name', 'secret' => 'owner-b']);
    }

    #[Test]
    public function dispatches_updated_event_for_existing_item(): void
    {
        $item = Item::factory()->create(['secret' => 'owner-a']);
        Event::fake();

        $this->sendRequest('owner-a', ['name' => 'New Name'])->assertOk();

        Event::assertDispatched(ItemUpdatedEvent::class, fn (ItemUpdatedEvent $event): bool => $event->model->is($item));
    }

    #[Test]
    public function creates_item_with_identifying_attributes_when_missing_and_returns_201(): void
    {
        Item::factory()->create(['name' => 'Other Name', 'secret' => 'owner-b']);

        $this->sendRequest('owner-a', ['name' => 'New Name'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseCount('items', 2);
        $this->assertDatabaseHas('items', ['name' => 'New Name', 'secret' => 'owner-a']);
    }

    #[Test]
    public function dispatches_created_event_when_item_is_missing(): void
    {
        Event::fake();

        $this->sendRequest('owner-a', ['name' => 'Brand New'])->assertCreated();

        Event::assertDispatched(ItemCreatedEvent::class, fn (ItemCreatedEvent $event): bool => $event->model->name === 'Brand New');
    }

    #[Test]
    public function identifying_attributes_take_precedence_over_request_values(): void
    {
        $this->sendRequest('owner-a', ['name' => 'New Name', 'secret' => 'owner-b'])->assertCreated();

        $this->assertDatabaseHas('items', ['name' => 'New Name', 'secret' => 'owner-a']);
        $this->assertDatabaseMissing('items', ['secret' => 'owner-b']);
    }

    #[Test]
    public function sets_non_fillable_identifying_attributes_on_created_item(): void
    {
        $this->app->instance(ItemController::class, new class extends ItemController
        {
            protected function getModelClass(): string
            {
                return Item::class;
            }

            protected function getUpsertAttributes(): array
            {
                return ['id' => 77];
            }
        });

        $this->putJson(route('item.upsert'), ['name' => 'New Name'])
            ->assertCreated()
            ->assertJsonPath('data.id', 77);

        $this->assertDatabaseHas('items', ['id' => 77, 'name' => 'New Name']);
    }

    #[Test]
    public function upserts_the_only_record_when_there_are_no_identifying_attributes(): void
    {
        $this->app->instance(ItemController::class, new class extends ItemController
        {
            protected function getModelClass(): string
            {
                return Item::class;
            }

            protected function getUpsertAttributes(): array
            {
                return [];
            }
        });

        $this->putJson(route('item.upsert'), ['name' => 'First Name'])->assertCreated();
        $this->putJson(route('item.upsert'), ['name' => 'Second Name'])->assertOk();

        $this->assertDatabaseCount('items', 1);
        $this->assertDatabaseHas('items', ['name' => 'Second Name']);
    }

    #[Test]
    public function updates_item_matching_all_identifying_attributes(): void
    {
        $this->bindControllerIdentifyingItemsByOwnerAndName();

        Item::factory()->create(['name' => 'Second Name', 'secret' => 'owner-a']);
        $item = Item::factory()->create(['name' => 'First Name', 'secret' => 'owner-a']);
        Item::factory()->create(['name' => 'First Name', 'secret' => 'owner-b']);

        $this->putJson(route('owners.items.upsert', ['owner-a', 'First Name']), ['name' => 'First Name'])
            ->assertOk()
            ->assertJsonPath('data.id', $item->id);

        $this->assertDatabaseCount('items', 3);
    }

    #[Test]
    public function creates_item_when_only_some_identifying_attributes_match(): void
    {
        $this->bindControllerIdentifyingItemsByOwnerAndName();

        Item::factory()->create(['name' => 'First Name', 'secret' => 'owner-a']);
        Item::factory()->create(['name' => 'Second Name', 'secret' => 'owner-b']);

        $this->putJson(route('owners.items.upsert', ['owner-b', 'First Name']), ['name' => 'First Name'])->assertCreated();

        $this->assertDatabaseCount('items', 3);
        $this->assertDatabaseHas('items', ['name' => 'First Name', 'secret' => 'owner-b']);
    }

    #[Test]
    public function updates_item_when_modify_query_joins_table_with_same_attribute_column(): void
    {
        $this->app->instance(ItemController::class, new class extends ItemController
        {
            protected function getModelClass(): string
            {
                return Item::class;
            }

            protected function modifyQuery(Builder $query): Builder
            {
                return $query
                    ->join('items as parents', 'parents.id', '=', 'items.id')
                    ->select('items.*');
            }
        });

        $item = Item::factory()->create(['name' => 'Old Name', 'secret' => 'owner-a']);

        $this->sendRequest('owner-a', ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('data.id', $item->id);

        $this->assertDatabaseHas('items', ['id' => $item->id, 'name' => 'New Name']);
    }

    #[Test]
    public function returns_404_without_creating_duplicate_when_item_exists_outside_modified_query(): void
    {
        $this->app->instance(ItemController::class, new class extends ItemController
        {
            protected function getModelClass(): string
            {
                return Item::class;
            }

            protected function modifyQuery(Builder $query): Builder
            {
                return $query->where('name', 'Visible Name');
            }
        });

        $item = Item::factory()->create(['name' => 'Hidden Name', 'secret' => 'owner-a']);

        $this->sendRequest('owner-a', ['name' => 'New Name'])->assertNotFound();

        $this->assertDatabaseCount('items', 1);
        $this->assertDatabaseHas('items', ['id' => $item->id, 'name' => 'Hidden Name']);
    }

    #[Test]
    public function returns_422_when_form_request_validation_fails(): void
    {
        Item::factory()->create(['secret' => 'owner-a']);

        $this->sendRequest('owner-a')
            ->assertUnprocessable()
            ->assertInvalid(['name' => 'The name field is required.']);
    }
}
