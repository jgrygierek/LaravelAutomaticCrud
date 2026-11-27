<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Support\Models;

class HiddenSecretItem extends Item
{
    protected $table = 'items';
    protected $hidden = ['secret'];
}
