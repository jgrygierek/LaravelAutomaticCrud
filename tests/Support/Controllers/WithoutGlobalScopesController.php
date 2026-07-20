<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Support\Controllers;

use JG\LaravelAutomaticCrud\Http\Controllers\CrudableController;
use JG\LaravelAutomaticCrud\Tests\Support\Models\ScopedItem;
use JG\LaravelAutomaticCrud\Tests\Support\Scopes\ExcludeSecretScope;

class WithoutGlobalScopesController extends CrudableController
{
    protected function getModelClass(): string
    {
        return ScopedItem::class;
    }

    protected function withoutGlobalScopes(): array
    {
        return [ExcludeSecretScope::class];
    }
}
