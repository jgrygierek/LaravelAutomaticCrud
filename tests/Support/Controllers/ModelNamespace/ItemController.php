<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Support\Controllers\ModelNamespace;

use JG\LaravelAutomaticCrud\Http\Controllers\CrudableController;

class ItemController extends CrudableController
{
    protected function getModelNamespace(): string
    {
        return 'JG\LaravelAutomaticCrud\Tests\Support\Models';
    }
}
