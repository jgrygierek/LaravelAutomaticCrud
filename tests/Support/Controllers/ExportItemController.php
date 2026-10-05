<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Support\Controllers;

use JG\LaravelAutomaticCrud\Http\Controllers\CrudableController;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use JG\LaravelAutomaticCrud\Traits\ExportTrait;

class ExportItemController extends CrudableController
{
    use ExportTrait;

    protected function getModelClass(): string
    {
        return Item::class;
    }
}
