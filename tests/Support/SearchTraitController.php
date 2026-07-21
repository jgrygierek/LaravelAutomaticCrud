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
        private readonly string $configName = 'default',
    ) {}

    public function getModelClass(): string
    {
        return $this->model;
    }

    protected function getConfigName(): string
    {
        return $this->configName;
    }

    public function exposeBuildQuery(): Builder
    {
        return $this->buildQuery(request());
    }
}
