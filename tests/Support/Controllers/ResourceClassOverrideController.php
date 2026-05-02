<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Support\Controllers;

use JG\LaravelAutomaticCrud\Http\Controllers\CrudableController;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use JG\LaravelAutomaticCrud\Tests\Support\Resources\SearchableItemResource;

class ResourceClassOverrideController extends CrudableController
{
    protected function getModelClass(): string
    {
        return Item::class;
    }

    protected function getResourceClass(): string
    {
        return SearchableItemResource::class;
    }
}
