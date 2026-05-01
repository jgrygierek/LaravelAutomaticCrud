<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Support\Filters;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class NameFilter
{
    public function handle(Builder $query, Closure $next): Builder
    {
        if (request()->filled('name')) {
            $query->where('name', request()->input('name'));
        }

        return $next($query);
    }
}
