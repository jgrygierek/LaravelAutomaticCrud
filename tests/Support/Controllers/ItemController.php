<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Support\Controllers;

use JG\LaravelAutomaticCrud\Http\Controllers\CrudableController;
use JG\LaravelAutomaticCrud\Traits\ExportTrait;
use JG\LaravelAutomaticCrud\Traits\UpsertTrait;

class ItemController extends CrudableController
{
    use ExportTrait;
    use UpsertTrait;

    protected function getUpsertAttributes(): array
    {
        return ['secret' => request()->route('owner')];
    }
}
