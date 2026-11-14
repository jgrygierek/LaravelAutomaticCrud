<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Support\Controllers;

use JG\LaravelAutomaticCrud\Http\Controllers\CrudableController;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;

class PreparedPaginationController extends CrudableController
{
    protected function getModelClass(): string
    {
        return Item::class;
    }

    protected function getRequestNamespace(): string
    {
        return 'JG\LaravelAutomaticCrud\Tests\Support\Requests\PreparedPagination';
    }
}
