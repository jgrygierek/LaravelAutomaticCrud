<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Support\Controllers;

use JG\LaravelAutomaticCrud\Http\Controllers\CrudableController;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;

class CustomPerPageController extends CrudableController
{
    public function getModelClass(): string
    {
        return Item::class;
    }

    protected function defaultItemsPerPage(): int
    {
        return 3;
    }
}
