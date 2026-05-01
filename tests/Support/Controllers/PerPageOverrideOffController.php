<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Support\Controllers;

use JG\LaravelAutomaticCrud\Http\Controllers\CrudableController;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;

class PerPageOverrideOffController extends CrudableController
{
    public function getModelClass(): string
    {
        return Item::class;
    }

    protected function isPerPageOverrideAllowed(): bool
    {
        return false;
    }
}
