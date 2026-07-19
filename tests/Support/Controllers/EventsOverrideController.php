<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Support\Controllers;

use JG\LaravelAutomaticCrud\Http\Controllers\CrudableController;
use JG\LaravelAutomaticCrud\Tests\Support\Events\ItemUpdatedEvent;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;

class EventsOverrideController extends CrudableController
{
    protected function getModelClass(): string
    {
        return Item::class;
    }

    protected function getEvents(): array
    {
        return [
            'Created' => ItemUpdatedEvent::class,
            'Deleted' => null,
        ];
    }
}
