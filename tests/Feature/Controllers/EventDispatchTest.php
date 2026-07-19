<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature\Controllers;

use Illuminate\Support\Facades\Event;
use JG\LaravelAutomaticCrud\Tests\Feature\CrudTestCase;
use JG\LaravelAutomaticCrud\Tests\Support\Events\ItemCreatedEvent;
use JG\LaravelAutomaticCrud\Tests\Support\Events\ItemDeletedEvent;
use JG\LaravelAutomaticCrud\Tests\Support\Events\ItemUpdatedEvent;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use PHPUnit\Framework\Attributes\Test;

final class EventDispatchTest extends CrudTestCase
{
    #[Test]
    public function dispatches_created_event_on_store(): void
    {
        Event::fake();

        $this->postJson(route('items.store'), ['name' => 'New Item'])->assertCreated();

        Event::assertDispatched(ItemCreatedEvent::class, fn (ItemCreatedEvent $event): bool => $event->model->name === 'New Item');
    }

    #[Test]
    public function dispatches_updated_event_on_update(): void
    {
        $item = Item::factory()->create(['name' => 'Old Name']);

        Event::fake();

        $this->putJson(route('items.update', $item), ['name' => 'New Name'])->assertOk();

        Event::assertDispatched(ItemUpdatedEvent::class, fn (ItemUpdatedEvent $event): bool => $event->model->is($item) && $event->model->name === 'New Name');
    }

    #[Test]
    public function dispatches_deleted_event_on_destroy(): void
    {
        $item = Item::factory()->create();

        Event::fake();

        $this->deleteJson(route('items.destroy', $item))->assertNoContent();

        Event::assertDispatched(ItemDeletedEvent::class, fn (ItemDeletedEvent $event): bool => $event->model->is($item));
    }
}
