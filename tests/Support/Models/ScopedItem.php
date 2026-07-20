<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Support\Models;

use JG\LaravelAutomaticCrud\Tests\Support\Scopes\ExcludeSecretScope;

class ScopedItem extends Item
{
    protected $table = 'items';

    protected static function booted(): void
    {
        static::addGlobalScope(new ExcludeSecretScope());
    }
}
