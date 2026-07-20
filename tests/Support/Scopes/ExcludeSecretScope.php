<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Support\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ExcludeSecretScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereNull('secret');
    }
}
