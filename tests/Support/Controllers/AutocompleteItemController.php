<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Support\Controllers;

use JG\LaravelAutomaticCrud\Http\Controllers\CrudableController;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use JG\LaravelAutomaticCrud\Traits\AutocompleteTrait;

class AutocompleteItemController extends CrudableController
{
    use AutocompleteTrait;

    protected function getModelClass(): string
    {
        return Item::class;
    }

    protected function getAutocompleteColumns(): array
    {
        return ['name', 'secret'];
    }
}
