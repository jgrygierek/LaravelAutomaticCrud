<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Traits;

trait ConfigTrait
{
    protected string $configName = 'default';

    protected function getConfig(string $key): mixed
    {
        $value = config("automatic-crud.configs.$this->configName.$key");
        if ($value !== null) {
            return $value;
        }

        if ($this->configName !== 'default') {
            $value = config("automatic-crud.configs.default.$key");
            if ($value !== null) {
                return $value;
            }
        }

        return match ($key) {
            'requests.force_custom', 'requests.only_validated' => false,
            'namespaces.request' => 'App\Http\Requests',
            'namespaces.model' => 'App\Models',
            'namespaces.resource' => 'App\Http\Resources',
            'pagination.paginate', 'pagination.allow_pagination_override', 'pagination.allow_per_page_override' => true,
            'pagination.per_page' => 10,
            default => null,
        };
    }
}
