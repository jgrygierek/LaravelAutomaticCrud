<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Traits;

use Illuminate\Support\Arr;

trait ConfigTrait
{
    protected function getConfigName(): string
    {
        return 'default';
    }

    protected function getConfig(string $key): mixed
    {
        $configName = $this->getConfigName();

        $value = config("automatic-crud.configs.$configName.$key");
        if ($value !== null) {
            return $value;
        }

        if ($configName !== 'default') {
            $value = config("automatic-crud.configs.default.$key");
            if ($value !== null) {
                return $value;
            }
        }

        return Arr::get((require __DIR__ . '/../../config/automatic-crud.php')['configs']['default'], $key);
    }
}
