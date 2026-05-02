<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Support\Models;

use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use JG\LaravelAutomaticCrud\Tests\Support\Factories\ItemFactory;

/**
 * @property int $id
 * @property string $name
 * @property string|null $secret
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[UseFactory(ItemFactory::class)]
class Item extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'secret'];
}
