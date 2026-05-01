<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Unit;

use Closure;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\ItemController;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use JG\LaravelAutomaticCrud\Tests\TestCase;
use JG\LaravelAutomaticCrud\Traits\ConfigTrait;
use JG\LaravelAutomaticCrud\Traits\ModelTrait;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

final class ModelTraitTest extends TestCase
{
    #[Test]
    public function resolves_model_by_convention_using_class_name(): void
    {
        $controller = new ItemController();

        $result = Closure::bind(fn () => $this->getModelClass(), $controller, $controller::class)();

        $this->assertSame(Item::class, $result);
    }

    #[Test]
    public function throws_when_no_model_found_by_convention(): void
    {
        config()->set('automatic-crud.defaults.namespaces.model', 'App\Models');

        $controller = new class
        {
            use ConfigTrait, ModelTrait;

            public function exposeGetModelClass(): string
            {
                return $this->getModelClass();
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Model class \[.+\] not found\./');

        $controller->exposeGetModelClass();
    }

    #[Test]
    public function get_model_namespace_returns_config_value(): void
    {
        config()->set('automatic-crud.defaults.namespaces.model', 'App\Models');

        $controller = new class
        {
            use ConfigTrait, ModelTrait;

            public function exposeGetModelNamespace(): string
            {
                return $this->getModelNamespace();
            }
        };

        $this->assertSame('App\Models', $controller->exposeGetModelNamespace());
    }

    #[Test]
    public function get_model_namespace_override_takes_precedence_over_config(): void
    {
        config()->set('automatic-crud.defaults.namespaces.model', 'App\Models');

        $controller = new class
        {
            use ConfigTrait, ModelTrait;

            protected function getModelNamespace(): string
            {
                return 'Custom\Models';
            }

            public function exposeGetModelNamespace(): string
            {
                return $this->getModelNamespace();
            }
        };

        $this->assertSame('Custom\Models', $controller->exposeGetModelNamespace());
    }
}
