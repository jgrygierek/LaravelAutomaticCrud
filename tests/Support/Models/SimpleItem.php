<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Support\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $secret
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class SimpleItem extends Model
{
    protected $table = 'items';
    protected $fillable = ['name', 'secret'];
}
