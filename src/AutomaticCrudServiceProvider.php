<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud;

use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AutomaticCrudServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/automatic-crud.php' => config_path('automatic-crud.php'),
        ], 'automatic-crud-config');

        $this->validateConfig();
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/automatic-crud.php',
            'automatic-crud',
        );
    }

    private function validateConfig(): void
    {
        $defaults = config('automatic-crud.defaults', []);

        $this->assertOnlyValidatedRequestsConfig($defaults, 'defaults');

        foreach (array_keys(config('automatic-crud.custom_configs', [])) as $customConfig) {
            $merged = array_replace_recursive($defaults, config("automatic-crud.custom_configs.$customConfig", []));
            $this->assertOnlyValidatedRequestsConfig((array) $merged, "custom_configs.$customConfig");
        }
    }

    private function assertOnlyValidatedRequestsConfig(array $config, string $context): void
    {
        $requests = $config['requests'] ?? [];

        if (($requests['only_validated'] ?? false) && !($requests['force_custom'] ?? false)) {
            throw new RuntimeException("Configuration error in automatic-crud.$context: requests.only_validated requires requests.force_custom to be enabled.");
        }
    }
}
