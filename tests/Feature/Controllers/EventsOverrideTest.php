<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature\Controllers;

use Illuminate\Support\Facades\Event;
use JG\LaravelAutomaticCrud\Tests\Feature\CrudTestCase;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\EventsOverrideController;
use JG\LaravelAutomaticCrud\Tests\Support\Events\ItemCreatedEvent;
use JG\LaravelAutomaticCrud\Tests\Support\Events\ItemDeletedEvent;
use JG\LaravelAutomaticCrud\Tests\Support\Events\ItemUpdatedEvent;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use PHPUnit\Framework\Attributes\Test;

final class EventsOverrideTest extends CrudTestCase
{
    protected function defineRoutes($router): void
    {
        $router->apiResource('override-events-items', EventsOverrideController::class);
    }

    #[Test]
    public function store_dispatches_overridden_event_class_instead_of_convention_class(): void
    {
        Event::fake();

        $this->postJson(route('override-events-items.store'), ['name' => 'New Item'])->assertCreated();

        Event::assertDispatched(ItemUpdatedEvent::class);
        Event::assertNotDispatched(ItemCreatedEvent::class);
    }

    #[Test]
    public function update_falls_back_to_convention_class_when_action_missing_from_overridden_map(): void
    {
        $item = Item::factory()->create();

        Event::fake();

        $this->putJson(route('override-events-items.update', $item), ['name' => 'Changed'])->assertOk();

        Event::assertDispatched(ItemUpdatedEvent::class);
    }

    #[Test]
    public function destroy_does_not_dispatch_event_when_explicitly_disabled_in_overridden_map(): void
    {
        $item = Item::factory()->create();

        Event::fake();

        $this->deleteJson(route('override-events-items.destroy', $item))->assertNoContent();

        Event::assertNotDispatched(ItemDeletedEvent::class);
    }
}
