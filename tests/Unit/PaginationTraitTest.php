<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Unit;

use JG\LaravelAutomaticCrud\Tests\TestCase;
use JG\LaravelAutomaticCrud\Traits\ConfigTrait;
use JG\LaravelAutomaticCrud\Traits\PaginationTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class PaginationTraitTest extends TestCase
{
    private object $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = new class
        {
            use ConfigTrait, PaginationTrait;

            public function exposeDefaultIsPaginationEnabled(): bool
            {
                return $this->defaultIsPaginationEnabled();
            }

            public function exposeDefaultItemsPerPage(): int
            {
                return $this->defaultItemsPerPage();
            }

            public function exposeisPaginationOverrideAllowedInQuery(): bool
            {
                return $this->isPaginationOverrideAllowedInQuery();
            }

            public function exposeisPerPageOverrideAllowedInQuery(): bool
            {
                return $this->isPerPageOverrideAllowedInQuery();
            }
        };
    }

    #[Test]
    #[DataProvider('defaultIsPaginationEnabledProvider')]
    public function default_is_pagination_enabled(array $config, bool $expected): void
    {
        config($config);

        $this->assertSame($expected, $this->controller->exposeDefaultIsPaginationEnabled());
    }

    public static function defaultIsPaginationEnabledProvider(): iterable
    {
        yield 'returns true by default' => [['automatic-crud' => null], true];
        yield 'returns value from config' => [['automatic-crud.configs.default.pagination.paginate' => false], false];
    }

    #[Test]
    #[DataProvider('defaultItemsPerPageProvider')]
    public function default_items_per_page(array $config, int $expected): void
    {
        config($config);

        $this->assertSame($expected, $this->controller->exposeDefaultItemsPerPage());
    }

    public static function defaultItemsPerPageProvider(): iterable
    {
        yield 'returns 10 by default' => [['automatic-crud' => null], 10];
        yield 'returns value from config' => [['automatic-crud.configs.default.pagination.per_page' => 25], 25];
    }

    #[Test]
    #[DataProvider('isPaginationOverrideAllowedInQueryProvider')]
    public function is_pagination_override_allowed(array $config, bool $expected): void
    {
        config($config);

        $this->assertSame($expected, $this->controller->exposeisPaginationOverrideAllowedInQuery());
    }

    public static function isPaginationOverrideAllowedInQueryProvider(): iterable
    {
        yield 'returns true by default' => [['automatic-crud' => null], true];
        yield 'returns value from config' => [['automatic-crud.configs.default.pagination.allow_pagination_override' => false], false];
    }

    #[Test]
    #[DataProvider('isPerPageOverrideAllowedInQueryProvider')]
    public function is_per_page_override_allowed(array $config, bool $expected): void
    {
        config($config);

        $this->assertSame($expected, $this->controller->exposeisPerPageOverrideAllowedInQuery());
    }

    public static function isPerPageOverrideAllowedInQueryProvider(): iterable
    {
        yield 'returns true by default' => [['automatic-crud' => null], true];
        yield 'returns value from config' => [['automatic-crud.configs.default.pagination.allow_per_page_override' => false], false];
    }
}
