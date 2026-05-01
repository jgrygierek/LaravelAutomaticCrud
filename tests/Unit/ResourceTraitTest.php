<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Unit;

use Illuminate\Http\Resources\Json\JsonResource;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use JG\LaravelAutomaticCrud\Tests\Support\Resources\ItemResource;
use JG\LaravelAutomaticCrud\Tests\Support\Resources\SearchableItemResource;
use JG\LaravelAutomaticCrud\Tests\Support\Resources\WithCollection\ItemCollectionResource as WithCollectionItemCollectionResource;
use JG\LaravelAutomaticCrud\Tests\TestCase;
use JG\LaravelAutomaticCrud\Traits\ConfigTrait;
use JG\LaravelAutomaticCrud\Traits\ResourceTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
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
        config()->set('automatic-crud.defaults.namespaces.resource', 'App\Http\Resources');

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
        config()->set('automatic-crud.defaults.namespaces.resource', 'App\Http\Resources');

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
