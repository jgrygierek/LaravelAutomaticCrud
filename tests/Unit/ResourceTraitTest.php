<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Unit;

use Illuminate\Http\Resources\Json\JsonResource;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use JG\LaravelAutomaticCrud\Tests\Support\Resources\ItemExportResource;
use JG\LaravelAutomaticCrud\Tests\Support\Resources\ItemResource;
use JG\LaravelAutomaticCrud\Tests\Support\Resources\SearchableItemResource;
use JG\LaravelAutomaticCrud\Tests\Support\Resources\WithCollection\ItemCollectionResource as WithCollectionItemCollectionResource;
use JG\LaravelAutomaticCrud\Tests\Support\Resources\WithExport\ItemExportResource as WithExportItemExportResource;
use JG\LaravelAutomaticCrud\Tests\TestCase;
use JG\LaravelAutomaticCrud\Traits\ConfigTrait;
use JG\LaravelAutomaticCrud\Traits\ResourceTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use stdClass;

final class ResourceTraitTest extends TestCase
{
    #[Test]
    #[DataProvider('resourceClassResolutionProvider')]
    public function resolves_resource_class(string $modelClass, string $expected): void
    {
        $controller = $this->makeController($modelClass);
        $this->assertSame($expected, $controller->exposeGetResourceClass());
    }

    public static function resourceClassResolutionProvider(): iterable
    {
        yield 'resource class found' => [Item::class, ItemResource::class];
        yield 'resource class not found' => [new stdClass()::class, JsonResource::class];
    }

    #[Test]
    #[DataProvider('resourceCollectionClassResolutionProvider')]
    public function resolves_resource_collection_class(string $modelClass, string $expected): void
    {
        $controller = $this->makeController($modelClass);
        $this->assertSame($expected, $controller->exposeGetResourceCollectionClass());
    }

    public static function resourceCollectionClassResolutionProvider(): iterable
    {
        yield 'collection falls back to resource class' => [Item::class, ItemResource::class];
        yield 'collection falls back to json resource' => [new stdClass()::class, JsonResource::class];
    }

    #[Test]
    public function resolves_collection_resource_class_when_dedicated_class_exists(): void
    {
        $controller = new class
        {
            use ConfigTrait, ResourceTrait;

            public function getModelClass(): string
            {
                return Item::class;
            }

            protected function getResourceNamespace(): string
            {
                return 'JG\LaravelAutomaticCrud\Tests\Support\Resources\WithCollection';
            }

            public function exposeGetResourceCollectionClass(): string
            {
                return $this->getResourceCollectionClass();
            }
        };

        $this->assertSame(WithCollectionItemCollectionResource::class, $controller->exposeGetResourceCollectionClass());
    }

    #[Test]
    public function get_resource_class_override_takes_precedence_over_convention(): void
    {
        $controller = new class
        {
            use ConfigTrait, ResourceTrait;

            public function getModelClass(): string
            {
                return Item::class;
            }

            protected function getResourceClass(): string
            {
                return SearchableItemResource::class;
            }

            public function exposeGetResourceClass(): string
            {
                return $this->getResourceClass();
            }

            public function exposeGetResourceCollectionClass(): string
            {
                return $this->getResourceCollectionClass();
            }
        };

        $this->assertSame(SearchableItemResource::class, $controller->exposeGetResourceClass());
        $this->assertSame(SearchableItemResource::class, $controller->exposeGetResourceCollectionClass());
    }

    #[Test]
    public function get_resource_namespace_returns_config_value(): void
    {
        config()->set('automatic-crud.configs.default.namespaces.resource', 'App\Http\Resources');

        $controller = new class
        {
            use ConfigTrait, ResourceTrait;

            public function getModelClass(): string
            {
                return '';
            }

            public function exposeGetResourceNamespace(): string
            {
                return $this->getResourceNamespace();
            }
        };

        $this->assertSame('App\Http\Resources', $controller->exposeGetResourceNamespace());
    }

    #[Test]
    public function get_resource_namespace_override_takes_precedence_over_config(): void
    {
        config()->set('automatic-crud.configs.default.namespaces.resource', 'App\Http\Resources');

        $controller = new class
        {
            use ConfigTrait, ResourceTrait;

            public function getModelClass(): string
            {
                return '';
            }

            protected function getResourceNamespace(): string
            {
                return 'Custom\Resources';
            }

            public function exposeGetResourceNamespace(): string
            {
                return $this->getResourceNamespace();
            }
        };

        $this->assertSame('Custom\Resources', $controller->exposeGetResourceNamespace());
    }

    #[Test]
    public function resolves_resource_class_by_convention_only_once(): void
    {
        $controller = new class
        {
            use ConfigTrait, ResourceTrait;

            public int $modelClassCalls = 0;

            public function getModelClass(): string
            {
                ++$this->modelClassCalls;

                return Item::class;
            }

            public function exposeGetResourceClass(): string
            {
                return $this->getResourceClass();
            }
        };

        $controller->exposeGetResourceClass();

        $this->assertSame(ItemResource::class, $controller->exposeGetResourceClass());
        $this->assertSame(1, $controller->modelClassCalls);
    }

    #[Test]
    public function resolves_resource_collection_class_by_convention_only_once(): void
    {
        $controller = new class
        {
            use ConfigTrait, ResourceTrait;

            public int $modelClassCalls = 0;

            public function getModelClass(): string
            {
                ++$this->modelClassCalls;

                return Item::class;
            }

            public function exposeGetResourceCollectionClass(): string
            {
                return $this->getResourceCollectionClass();
            }
        };

        $controller->exposeGetResourceCollectionClass();

        $this->assertSame(ItemResource::class, $controller->exposeGetResourceCollectionClass());
        $this->assertSame(2, $controller->modelClassCalls);
    }

    #[Test]
    #[DataProvider('resourceExportClassResolutionProvider')]
    public function resolves_resource_export_class(string $namespace, string $expected): void
    {
        $controller = new class($namespace)
        {
            use ConfigTrait, ResourceTrait;

            public function __construct(private readonly string $namespace) {}

            public function getModelClass(): string
            {
                return Item::class;
            }

            protected function getResourceNamespace(): string
            {
                return $this->namespace;
            }

            public function exposeGetResourceExportClass(): string
            {
                return $this->getResourceExportClass();
            }
        };

        $this->assertSame($expected, $controller->exposeGetResourceExportClass());
    }

    public static function resourceExportClassResolutionProvider(): iterable
    {
        yield 'default namespace' => ['JG\LaravelAutomaticCrud\Tests\Support\Resources', ItemExportResource::class];
        yield 'custom namespace' => ['JG\LaravelAutomaticCrud\Tests\Support\Resources\WithExport', WithExportItemExportResource::class];
    }

    #[Test]
    public function throws_when_export_resource_class_is_missing(): void
    {
        $controller = new class
        {
            use ConfigTrait, ResourceTrait;

            public function getModelClass(): string
            {
                return Item::class;
            }

            protected function getResourceNamespace(): string
            {
                return 'Missing\Resources';
            }

            public function exposeGetResourceExportClass(): string
            {
                return $this->getResourceExportClass();
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Export resource class [Missing\Resources\ItemExportResource] not found.');

        $controller->exposeGetResourceExportClass();
    }

    #[Test]
    public function resolves_resource_export_class_by_convention_only_once(): void
    {
        $controller = new class
        {
            use ConfigTrait, ResourceTrait;

            public int $modelClassCalls = 0;

            public function getModelClass(): string
            {
                ++$this->modelClassCalls;

                return Item::class;
            }

            public function exposeGetResourceExportClass(): string
            {
                return $this->getResourceExportClass();
            }
        };

        $controller->exposeGetResourceExportClass();

        $this->assertSame(ItemExportResource::class, $controller->exposeGetResourceExportClass());
        $this->assertSame(1, $controller->modelClassCalls);
    }

    private function makeController(string $modelClass): object
    {
        return new class($modelClass)
        {
            use ConfigTrait, ResourceTrait;

            public function __construct(private readonly string $model) {}

            public function getModelClass(): string
            {
                return $this->model;
            }

            public function exposeGetResourceClass(): string
            {
                return $this->getResourceClass();
            }

            public function exposeGetResourceCollectionClass(): string
            {
                return $this->getResourceCollectionClass();
            }
        };
    }
}
