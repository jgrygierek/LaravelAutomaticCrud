<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Unit;

use Illuminate\Http\Request;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use JG\LaravelAutomaticCrud\Tests\Support\Requests\Items\DestroyItemRequest;
use JG\LaravelAutomaticCrud\Tests\Support\Requests\Items\ShowItemRequest;
use JG\LaravelAutomaticCrud\Tests\Support\Requests\Items\StoreItemRequest;
use JG\LaravelAutomaticCrud\Tests\Support\Requests\Items\UpdateItemRequest;
use JG\LaravelAutomaticCrud\Tests\TestCase;
use JG\LaravelAutomaticCrud\Traits\ConfigTrait;
use JG\LaravelAutomaticCrud\Traits\RequestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

final class RequestTraitTest extends TestCase
{
    private object $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = new class
        {
            use ConfigTrait, RequestTrait;

            public function getModelClass(): string
            {
                return Item::class;
            }

            public function exposeGetRequestClass(string $action): ?string
            {
                return $this->getRequestClass($action);
            }

            public function exposeGetRequestDirName(string $modelClass): string
            {
                return $this->getRequestDirName($modelClass);
            }

            public function exposeGetRequestFileName(string $modelClass, string $action): string
            {
                return $this->getRequestFileName($modelClass, $action);
            }
        };
    }

    #[Test]
    #[DataProvider('existingRequestClassProvider')]
    public function resolves_request_class_for_existing_action(string $action, string $expected): void
    {
        $this->assertSame($expected, $this->controller->exposeGetRequestClass($action));
    }

    public static function existingRequestClassProvider(): iterable
    {
        yield 'store' => ['Store', StoreItemRequest::class];
        yield 'update' => ['Update', UpdateItemRequest::class];
        yield 'show' => ['Show', ShowItemRequest::class];
        yield 'destroy' => ['Destroy', DestroyItemRequest::class];
    }

    #[Test]
    #[DataProvider('nonExistingRequestClassProvider')]
    public function returns_null_when_request_class_does_not_exist(string $action): void
    {
        $this->assertNull($this->controller->exposeGetRequestClass($action));
    }

    public static function nonExistingRequestClassProvider(): iterable
    {
        yield 'index' => ['Index'];
        yield 'empty action' => [''];
    }

    #[Test]
    public function throws_exception_when_class_not_found_and_force_custom_requests_is_enabled(): void
    {
        config()->set('automatic-crud.defaults.requests.force_custom', true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Request class \[.+\] not found\./');

        $this->controller->exposeGetRequestClass('Index');
    }

    #[Test]
    public function returns_class_when_it_exists_and_force_custom_requests_is_enabled(): void
    {
        config()->set('automatic-crud.defaults.requests.force_custom', true);

        $this->assertSame(StoreItemRequest::class, $this->controller->exposeGetRequestClass('Store'));
    }

    #[Test]
    public function throws_exception_when_class_not_found_and_only_validated_requests_is_enabled(): void
    {
        config()->set('automatic-crud.defaults.requests.only_validated', true);
        config()->set('automatic-crud.defaults.requests.force_custom', true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Request class \[.+\] not found\./');

        $this->controller->exposeGetRequestClass('Index');
    }

    #[Test]
    public function returns_class_when_it_exists_and_only_validated_requests_is_enabled(): void
    {
        config()->set('automatic-crud.defaults.requests.only_validated', true);
        config()->set('automatic-crud.defaults.requests.force_custom', true);

        $this->assertSame(StoreItemRequest::class, $this->controller->exposeGetRequestClass('Store'));
    }

    #[Test]
    public function throws_exception_when_only_validated_is_enabled_and_request_is_not_form_request(): void
    {
        config()->set('automatic-crud.defaults.requests.only_validated', true);

        $controller = new class
        {
            use ConfigTrait, RequestTrait;

            public function getModelClass(): string
            {
                return Item::class;
            }

            protected function applyRequest(): Request
            {
                return request();
            }

            public function exposeGetAllowedRequestValues(): array
            {
                return $this->getAllowedRequestValues();
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/must extend FormRequest when requests\.only_validated is enabled/');

        $controller->exposeGetAllowedRequestValues();
    }

    #[Test]
    public function request_dir_name_pluralizes_model_name(): void
    {
        $this->assertSame('Items', $this->controller->exposeGetRequestDirName(Item::class));
    }

    #[Test]
    public function request_file_name_builds_from_action_and_model(): void
    {
        $this->assertSame('StoreItemRequest', $this->controller->exposeGetRequestFileName(Item::class, 'Store'));
    }

    #[Test]
    public function get_request_namespace_returns_config_value(): void
    {
        config()->set('automatic-crud.defaults.namespaces.request', 'App\Http\Requests');

        $controller = new class
        {
            use ConfigTrait, RequestTrait;

            public function getModelClass(): string
            {
                return Item::class;
            }

            public function exposeGetRequestNamespace(): string
            {
                return $this->getRequestNamespace();
            }
        };

        $this->assertSame('App\Http\Requests', $controller->exposeGetRequestNamespace());
    }

    #[Test]
    public function get_request_namespace_override_takes_precedence_over_config(): void
    {
        config()->set('automatic-crud.defaults.namespaces.request', 'App\Http\Requests');

        $controller = new class
        {
            use ConfigTrait, RequestTrait;

            public function getModelClass(): string
            {
                return Item::class;
            }

            protected function getRequestNamespace(): string
            {
                return 'Custom\Requests';
            }

            public function exposeGetRequestNamespace(): string
            {
                return $this->getRequestNamespace();
            }
        };

        $this->assertSame('Custom\Requests', $controller->exposeGetRequestNamespace());
    }
}
