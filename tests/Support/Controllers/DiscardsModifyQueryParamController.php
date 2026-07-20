<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Support\Controllers;

use Illuminate\Database\Eloquent\Builder;
use JG\LaravelAutomaticCrud\Http\Controllers\CrudableController;
use JG\LaravelAutomaticCrud\Tests\Support\Models\ScopedItem;
use JG\LaravelAutomaticCrud\Tests\Support\Scopes\ExcludeSecretScope;

class DiscardsModifyQueryParamController extends CrudableController
{
    protected function getModelClass(): string
    {
        return ScopedItem::class;
    }

    protected function modifyQuery(Builder $query): Builder
    {
        return $this->getModelClass()::query();
    }

    protected function withoutGlobalScopes(): array
    {
        return [ExcludeSecretScope::class];
    }
}
