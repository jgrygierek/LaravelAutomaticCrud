<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Support\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;

class VisibleNameItem extends Item
{
    protected $table = 'items';
    protected $visible = ['id', 'name', 'label'];
    protected $appends = ['label'];

    protected function label(): Attribute
    {
        return Attribute::get(fn (): string => strtoupper($this->name));
    }
}
