<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Support\Controllers;

use Illuminate\Database\Eloquent\Builder;
use JG\LaravelAutomaticCrud\Http\Controllers\CrudableController;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;

class ModifyQueryController extends CrudableController
{
    protected function getModelClass(): string
    {
        return Item::class;
    }

    protected function modifyQuery(Builder $query): Builder
    {
        return $query->whereNull('secret');
    }
}
