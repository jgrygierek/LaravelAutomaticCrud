<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Support\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 */
class KeylessItem extends Model
{
    public $incrementing = false;
    protected $table = 'items';
    protected $primaryKey;
}
