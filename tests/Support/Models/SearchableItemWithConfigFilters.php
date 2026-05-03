<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Support\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use JG\LaravelAutomaticCrud\Http\Interfaces\SearchableInterface;
use JG\LaravelAutomaticCrud\Tests\Support\Filters\IdFilter;
use JG\LaravelAutomaticCrud\Tests\Support\Filters\NameFilter;

/**
 * @property int $id
 * @property string $name
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class SearchableItemWithConfigFilters extends Model implements SearchableInterface
{
    protected $table = 'items';
    protected $fillable = ['name'];

    public function searchFilters(): array
    {
        return [
            'default' => [NameFilter::class],
            'custom' => [IdFilter::class],
        ];
    }
}
