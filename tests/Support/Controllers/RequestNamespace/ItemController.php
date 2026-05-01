<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Support\Controllers\RequestNamespace;

use JG\LaravelAutomaticCrud\Http\Controllers\CrudableController;

class ItemController extends CrudableController
{
    protected function getRequestNamespace(): string
    {
        return 'JG\LaravelAutomaticCrud\Tests\Support\Requests';
    }
}
