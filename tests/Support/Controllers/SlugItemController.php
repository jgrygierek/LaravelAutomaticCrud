<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Support\Controllers;

use JG\LaravelAutomaticCrud\Http\Controllers\CrudableController;
use JG\LaravelAutomaticCrud\Traits\UpsertTrait;

class SlugItemController extends CrudableController
{
    use UpsertTrait;
}
