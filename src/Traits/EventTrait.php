<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Traits;

use Illuminate\Database\Eloquent\Model;
use JG\LaravelAutomaticCrud\Enums\EventAction;

trait EventTrait
{
    abstract protected function getModelClass(): string;

    abstract protected function getConfig(string $key): mixed;

    protected function getEventNamespace(): string
    {
        return $this->getConfig('namespaces.event');
    }

    /**
     * @return array<string, class-string|null>
     */
    protected function getEvents(): array
    {
        return [];
    }

    protected function dispatchEvent(EventAction $action, Model $model): void
    {
        $events = $this->getEvents();

        $class = array_key_exists($action->value, $events)
            ? $events[$action->value]
            : $this->resolveEventClass($action);

        if ($class) {
            event(new $class($model));
        }
    }

    private function resolveEventClass(EventAction $action): ?string
    {
        $class = $this->getEventNamespace() . '\\' . $this->getEventFileName($action);

        return class_exists($class) ? $class : null;
    }

    private function getEventFileName(EventAction $action): string
    {
        return class_basename($this->getModelClass()) . $action->value . 'Event';
    }
}
