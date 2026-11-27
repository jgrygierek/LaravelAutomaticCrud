<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Unit;

use JG\LaravelAutomaticCrud\Tests\Support\Controllers\ExportItemController;
use JG\LaravelAutomaticCrud\Tests\Support\Resources\ItemExportResource;
use JG\LaravelAutomaticCrud\Tests\Support\Resources\WithExport\ItemExportResource as WithExportItemExportResource;
use JG\LaravelAutomaticCrud\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

final class ExportTraitTest extends TestCase
{
    #[Test]
    #[DataProvider('resourceExportClassResolutionProvider')]
    public function resolves_resource_export_class(string $namespace, string $expected): void
    {
        $this->assertSame($expected, $this->makeController($namespace)->exposeGetResourceExportClass());
    }

    public static function resourceExportClassResolutionProvider(): iterable
    {
        yield 'default namespace' => ['JG\LaravelAutomaticCrud\Tests\Support\Resources', ItemExportResource::class];
        yield 'custom namespace' => ['JG\LaravelAutomaticCrud\Tests\Support\Resources\WithExport', WithExportItemExportResource::class];
    }

    #[Test]
    public function throws_when_export_resource_class_is_missing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Export resource class [Missing\Resources\ItemExportResource] not found.');

        $this->makeController('Missing\Resources')->exposeGetResourceExportClass();
    }

    #[Test]
    public function resolves_resource_export_class_by_convention_only_once(): void
    {
        $controller = $this->makeController('JG\LaravelAutomaticCrud\Tests\Support\Resources');

        $controller->exposeGetResourceExportClass();

        $this->assertSame(ItemExportResource::class, $controller->exposeGetResourceExportClass());
        $this->assertSame(1, $controller->namespaceCalls);
    }

    private function makeController(string $namespace): ExportItemController
    {
        return new class($namespace) extends ExportItemController
        {
            public int $namespaceCalls = 0;

            public function __construct(private readonly string $namespace) {}

            protected function getResourceNamespace(): string
            {
                ++$this->namespaceCalls;

                return $this->namespace;
            }

            public function exposeGetResourceExportClass(): string
            {
                return $this->getResourceExportClass();
            }
        };
    }
}
