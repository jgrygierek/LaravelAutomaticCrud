<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Support\Models;

class SecondaryConnectionItem extends Item
{
    protected $connection = 'secondary';
    protected $table = 'items';
}
