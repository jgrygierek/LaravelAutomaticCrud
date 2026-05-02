<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Unit;

use JG\LaravelAutomaticCrud\Tests\TestCase;
use JG\LaravelAutomaticCrud\Traits\ConfigTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class ConfigTraitTest extends TestCase
{
    private object $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = new class
        {
            use ConfigTrait;

            public function exposeGetConfig(string $key): mixed
            {
                return $this->getConfig($key);
            }
        };
    }

    #[Test]
    #[DataProvider('fallbackValuesProvider')]
    public function returns_hardcoded_fallback_value_when_no_config_set(string $key, mixed $expected): void
    {
        config(['automatic-crud' => null]);

        $this->assertSame($expected, $this->controller->exposeGetConfig($key));
    }

    public static function fallbackValuesProvider(): iterable
    {
        yield 'namespaces.request' => ['namespaces.request', 'App\Http\Requests'];
        yield 'namespaces.model' => ['namespaces.model', 'App\Models'];
        yield 'namespaces.resource' => ['namespaces.resource', 'App\Http\Resources'];
        yield 'pagination.paginate' => ['pagination.paginate', true];
        yield 'pagination.per_page' => ['pagination.per_page', 10];
        yield 'requests.only_validated' => ['requests.only_validated', false];
        yield 'requests.force_custom' => ['requests.force_custom', false];
    }

    #[Test]
    public function returns_null_for_unknown_key(): void
    {
        $this->assertNull($this->controller->exposeGetConfig('unknown_key'));
    }

    #[Test]
    public function returns_value_from_config_defaults(): void
    {
        config(['automatic-crud.defaults.pagination.per_page' => 25]);

        $this->assertSame(25, $this->controller->exposeGetConfig('pagination.per_page'));
    }

    #[Test]
    public function custom_config_value_overrides_default(): void
    {
        config([
            'automatic-crud.defaults.pagination.per_page' => 10,
            'automatic-crud.custom_configs.custom.pagination.per_page' => 50,
        ]);

        $this->assertSame(50, $this->makeControllerWithCustomConfig()->exposeGetConfig('pagination.per_page'));
    }

    #[Test]
    public function falls_back_to_default_when_custom_config_key_missing(): void
    {
        config([
            'automatic-crud.defaults.pagination.per_page' => 20,
            'automatic-crud.custom_configs.custom.pagination.paginate' => false,
        ]);

        $this->assertSame(20, $this->makeControllerWithCustomConfig()->exposeGetConfig('pagination.per_page'));
    }

    #[Test]
    public function empty_custom_config_skips_lookup(): void
    {
        config([
            'automatic-crud.defaults.pagination.per_page' => 15,
            'automatic-crud.custom_configs.custom.pagination.per_page' => 99,
        ]);

        $this->assertSame(15, $this->controller->exposeGetConfig('pagination.per_page'));
    }

    private function makeControllerWithCustomConfig(): object
    {
        return new class
        {
            use ConfigTrait;

            public function __construct()
            {
                $this->customConfig = 'custom';
            }

            public function exposeGetConfig(string $key): mixed
            {
                return $this->getConfig($key);
            }
        };
    }
}
