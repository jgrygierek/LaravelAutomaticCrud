<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Traits;

trait ConfigTrait
{
    protected string $customConfig = '';

    protected function getConfig(string $key): mixed
    {
        if ($this->customConfig !== '') {
            $customConfigValue = config("automatic-crud.custom_configs.$this->customConfig.$key");

            if ($customConfigValue !== null) {
                return $customConfigValue;
            }
        }

        $defaultValue = config("automatic-crud.defaults.$key");

        return $defaultValue ?? match ($key) {
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
