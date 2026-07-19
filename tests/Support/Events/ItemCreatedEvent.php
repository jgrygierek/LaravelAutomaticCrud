<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Support\Events;

use Illuminate\Database\Eloquent\Model;

class ItemCreatedEvent
{
    public function __construct(public readonly Model $model) {}
}
