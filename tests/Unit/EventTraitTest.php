<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use JG\LaravelAutomaticCrud\Enums\EventAction;
use JG\LaravelAutomaticCrud\Tests\Support\Events\ItemCreatedEvent;
use JG\LaravelAutomaticCrud\Tests\Support\Events\ItemUpdatedEvent;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use JG\LaravelAutomaticCrud\Tests\Support\Models\SimpleItem;
use JG\LaravelAutomaticCrud\Tests\TestCase;
use JG\LaravelAutomaticCrud\Traits\ConfigTrait;
use JG\LaravelAutomaticCrud\Traits\EventTrait;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

final class EventTraitTest extends TestCase
{
    #[Test]
    public function dispatches_event_when_convention_class_exists(): void
    {
        Event::fake();

        $item = $this->createStub(Item::class);
        $this->makeController(Item::class)->exposeDispatchEvent(EventAction::Created, $item);

        Event::assertDispatched(ItemCreatedEvent::class, fn (ItemCreatedEvent $event): bool => $event->model === $item);
    }

    #[Test]
    public function defers_dispatch_until_enclosing_transaction_commits(): void
    {
        Event::fake();

        $item = $this->createStub(Item::class);
        $controller = $this->makeController(Item::class);

        DB::transaction(function () use ($controller, $item): void {
            $controller->exposeDispatchEvent(EventAction::Created, $item);

            Event::assertNotDispatched(ItemCreatedEvent::class);
        });

        Event::assertDispatched(ItemCreatedEvent::class);
    }

    #[Test]
    public function does_not_dispatch_event_when_enclosing_transaction_rolls_back(): void
    {
        Event::fake();

        $item = $this->createStub(Item::class);
        $controller = $this->makeController(Item::class);

        try {
            DB::transaction(function () use ($controller, $item): void {
                $controller->exposeDispatchEvent(EventAction::Created, $item);

                throw new RuntimeException('Forced rollback.');
            });
        } catch (RuntimeException) {
        }

        Event::assertNotDispatched(ItemCreatedEvent::class);
    }

    #[Test]
    public function does_not_dispatch_event_when_convention_class_missing(): void
    {
        Event::fake();

        $this->makeController(SimpleItem::class)->exposeDispatchEvent(EventAction::Created, $this->createStub(SimpleItem::class));

        Event::assertNothingDispatched();
    }

    #[Test]
    public function dispatches_class_from_overridden_events_map(): void
    {
        Event::fake();

        $item = $this->createStub(Item::class);
        $controller = new class($item)
        {
            use ConfigTrait, EventTrait;

            public function __construct(private readonly Model $item) {}

            public function getModelClass(): string
            {
                return Item::class;
            }

            protected function getEvents(): array
            {
                return ['Created' => ItemUpdatedEvent::class];
            }

            public function exposeDispatchEvent(EventAction $action, Model $model): void
            {
                $this->dispatchEvent($action, $model);
            }
        };

        $controller->exposeDispatchEvent(EventAction::Created, $item);

        Event::assertDispatched(ItemUpdatedEvent::class);
        Event::assertNotDispatched(ItemCreatedEvent::class);
    }

    #[Test]
    public function throws_when_overridden_events_map_references_missing_class(): void
    {
        Event::fake();

        $controller = new class
        {
            use ConfigTrait, EventTrait;

            public function getModelClass(): string
            {
                return Item::class;
            }

            protected function getEvents(): array
            {
                return ['Created' => 'JG\LaravelAutomaticCrud\Tests\Support\Events\MissingEvent'];
            }

            public function exposeDispatchEvent(EventAction $action, Model $model): void
            {
                $this->dispatchEvent($action, $model);
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Event class [JG\LaravelAutomaticCrud\Tests\Support\Events\MissingEvent] not found.');

        $controller->exposeDispatchEvent(EventAction::Created, $this->createStub(Item::class));
    }

    #[Test]
    public function does_not_dispatch_event_when_overridden_events_map_disables_action(): void
    {
        Event::fake();

        $controller = new class
        {
            use ConfigTrait, EventTrait;

            public function getModelClass(): string
            {
                return Item::class;
            }

            protected function getEvents(): array
            {
                return ['Created' => null];
            }

            public function exposeDispatchEvent(EventAction $action, Model $model): void
            {
                $this->dispatchEvent($action, $model);
            }
        };

        $controller->exposeDispatchEvent(EventAction::Created, $this->createStub(Item::class));

        Event::assertNothingDispatched();
    }

    #[Test]
    public function falls_back_to_convention_class_for_action_missing_from_overridden_events_map(): void
    {
        Event::fake();

        $item = $this->createStub(Item::class);
        $controller = new class($item)
        {
            use ConfigTrait, EventTrait;

            public function __construct(private readonly Model $item) {}

            public function getModelClass(): string
            {
                return Item::class;
            }

            protected function getEvents(): array
            {
                return ['Created' => null];
            }

            public function exposeDispatchEvent(EventAction $action, Model $model): void
            {
                $this->dispatchEvent($action, $model);
            }
        };

        $controller->exposeDispatchEvent(EventAction::Updated, $item);

        Event::assertDispatched(ItemUpdatedEvent::class);
    }

    #[Test]
    public function get_event_namespace_returns_config_value(): void
    {
        config()->set('automatic-crud.configs.default.namespaces.event', 'App\Events');

        $controller = new class
        {
            use ConfigTrait, EventTrait;

            public function getModelClass(): string
            {
                return '';
            }

            public function exposeGetEventNamespace(): string
            {
                return $this->getEventNamespace();
            }
        };

        $this->assertSame('App\Events', $controller->exposeGetEventNamespace());
    }

    #[Test]
    public function get_event_namespace_override_takes_precedence_over_config(): void
    {
        config()->set('automatic-crud.configs.default.namespaces.event', 'App\Events');

        $controller = new class
        {
            use ConfigTrait, EventTrait;

            public function getModelClass(): string
            {
                return '';
            }

            protected function getEventNamespace(): string
            {
                return 'Custom\Events';
            }

            public function exposeGetEventNamespace(): string
            {
                return $this->getEventNamespace();
            }
        };

        $this->assertSame('Custom\Events', $controller->exposeGetEventNamespace());
    }

    private function makeController(string $modelClass): object
    {
        return new class($modelClass)
        {
            use ConfigTrait, EventTrait;

            public function __construct(private readonly string $model) {}

            public function getModelClass(): string
            {
                return $this->model;
            }

            public function exposeDispatchEvent(EventAction $action, Model $model): void
            {
                $this->dispatchEvent($action, $model);
            }
        };
    }
}
