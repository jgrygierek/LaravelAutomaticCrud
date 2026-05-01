<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Support\Controllers\ResourceNamespace;

use JG\LaravelAutomaticCrud\Http\Controllers\CrudableController;

class ItemController extends CrudableController
{
    protected function getResourceNamespace(): string
    {
        return 'JG\LaravelAutomaticCrud\Tests\Support\Resources';
    }
}
