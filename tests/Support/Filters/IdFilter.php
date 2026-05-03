<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Support\Filters;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class IdFilter
{
    public function handle(Builder $query, Closure $next): Builder
    {
        if (request()->filled('id')) {
            $query->where('id', request()->input('id'));
        }

        return $next($query);
    }
}
