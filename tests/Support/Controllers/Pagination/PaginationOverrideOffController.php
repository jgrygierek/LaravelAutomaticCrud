<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Support\Controllers\Pagination;

use JG\LaravelAutomaticCrud\Http\Controllers\CrudableController;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;

class PaginationOverrideOffController extends CrudableController
{
    public function getModelClass(): string
    {
        return Item::class;
    }

    protected function isPaginationOverrideAllowedInQuery(): bool
    {
        return false;
    }
}
