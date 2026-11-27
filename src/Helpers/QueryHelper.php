<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Helpers;

use Illuminate\Database\Eloquent\Builder;

final class QueryHelper
{
    public static function groupWheres(Builder $query): Builder
    {
        $base = $query->getQuery();
        $nested = $base->forNestedWhere()->mergeWheres($base->wheres, $base->getRawBindings()['where']);

        $base->wheres = [];
        $base->bindings['where'] = [];

        $base->addNestedWhereQuery($nested);

        return $query;
    }

    public static function isSorted(Builder $query): bool
    {
        return !empty($query->getQuery()->orders);
    }
}
