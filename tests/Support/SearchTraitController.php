<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Support;

use Illuminate\Database\Eloquent\Builder;
use JG\LaravelAutomaticCrud\Traits\SearchTrait;

class SearchTraitController
{
    use SearchTrait;

    public function __construct(
        private readonly string $model,
        string $configName = 'default',
    ) {
        $this->configName = $configName;
    }

    public function getModelClass(): string
    {
        return $this->model;
    }

    public function exposeBuildQuery(): Builder
    {
        return $this->buildQuery(request());
    }
}
