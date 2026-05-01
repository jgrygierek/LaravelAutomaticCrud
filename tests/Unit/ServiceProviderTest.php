<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Unit;

use JG\LaravelAutomaticCrud\AutomaticCrudServiceProvider;
use JG\LaravelAutomaticCrud\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

final class ServiceProviderTest extends TestCase
{
    #[Test]
    public function throws_on_boot_when_only_validated_requests_enabled_without_force_custom_requests(): void
    {
        $provider = new AutomaticCrudServiceProvider($this->app);
        $provider->register();

        config()->set('automatic-crud.defaults.requests.only_validated', true);
        config()->set('automatic-crud.defaults.requests.force_custom', false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Configuration error in automatic-crud.defaults: requests.only_validated requires requests.force_custom to be enabled.');

        $provider->boot();
    }

    #[Test]
    public function throws_on_boot_when_custom_config_has_only_validated_without_force_custom(): void
    {
        $provider = new AutomaticCrudServiceProvider($this->app);
        $provider->register();

        config()->set('automatic-crud.custom_configs.strict', [
            'requests' => [
                'only_validated' => true,
                'force_custom' => false,
            ],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Configuration error in automatic-crud.custom_configs.strict: requests.only_validated requires requests.force_custom to be enabled.');

        $provider->boot();
    }

    #[Test]
    public function does_not_throw_when_both_flags_are_enabled_in_defaults(): void
    {
        $provider = new AutomaticCrudServiceProvider($this->app);
        $provider->register();

        config()->set('automatic-crud.defaults.requests.only_validated', true);
        config()->set('automatic-crud.defaults.requests.force_custom', true);

        $provider->boot();

        $this->assertTrue(true);
    }

    #[Test]
    public function does_not_throw_when_custom_config_inherits_force_custom_from_defaults(): void
    {
        $provider = new AutomaticCrudServiceProvider($this->app);
        $provider->register();

        config()->set('automatic-crud.defaults.requests.force_custom', true);
        config()->set('automatic-crud.custom_configs.strict', ['requests' => ['only_validated' => true]]);

        $provider->boot();

        $this->assertTrue(true);
    }
}
