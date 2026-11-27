<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Unit;

use JG\LaravelAutomaticCrud\Http\Resources\AutocompleteResource;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\AutocompleteItemController;
use JG\LaravelAutomaticCrud\Tests\Support\Resources\ItemAutocompleteResource;
use JG\LaravelAutomaticCrud\Tests\Support\Resources\WithAutocomplete\ItemAutocompleteResource as WithAutocompleteItemAutocompleteResource;
use JG\LaravelAutomaticCrud\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class AutocompleteTraitTest extends TestCase
{
    #[Test]
    #[DataProvider('resourceAutocompleteClassResolutionProvider')]
    public function resolves_resource_autocomplete_class(string $namespace, string $expected): void
    {
        $this->assertSame($expected, $this->makeController($namespace)->exposeGetResourceAutocompleteClass());
    }

    public static function resourceAutocompleteClassResolutionProvider(): iterable
    {
        yield 'default namespace' => ['JG\LaravelAutomaticCrud\Tests\Support\Resources', ItemAutocompleteResource::class];
        yield 'custom namespace' => ['JG\LaravelAutomaticCrud\Tests\Support\Resources\WithAutocomplete', WithAutocompleteItemAutocompleteResource::class];
        yield 'falls back to package resource' => ['JG\LaravelAutomaticCrud\Tests\Support\Resources\WithCollection', AutocompleteResource::class];
    }

    #[Test]
    public function resolves_resource_autocomplete_class_by_convention_only_once(): void
    {
        $controller = $this->makeController('JG\LaravelAutomaticCrud\Tests\Support\Resources\WithCollection');

        $controller->exposeGetResourceAutocompleteClass();

        $this->assertSame(AutocompleteResource::class, $controller->exposeGetResourceAutocompleteClass());
        $this->assertSame(1, $controller->namespaceCalls);
    }

    private function makeController(string $namespace): AutocompleteItemController
    {
        return new class($namespace) extends AutocompleteItemController
        {
            public int $namespaceCalls = 0;

            public function __construct(private readonly string $namespace) {}

            protected function getResourceNamespace(): string
            {
                ++$this->namespaceCalls;

                return $this->namespace;
            }

            public function exposeGetResourceAutocompleteClass(): string
            {
                return $this->getResourceAutocompleteClass();
            }
        };
    }
}
