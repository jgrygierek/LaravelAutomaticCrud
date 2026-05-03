<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature\Requests;

use JG\LaravelAutomaticCrud\Tests\Feature\CrudTestCase;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\RequestNamespace\ItemController as RequestNamespaceItemController;
use PHPUnit\Framework\Attributes\Test;

final class RequestNamespaceTest extends CrudTestCase
{
    protected function defineRoutes($router): void
    {
        $router->post('/request-ns/items', [RequestNamespaceItemController::class, 'store']);
    }

    #[Test]
    public function request_namespace_method_overrides_config(): void
    {
        config([
            'automatic-crud.configs.default.namespaces.request' => 'Wrong\Requests',
            'automatic-crud.configs.default.requests.force_custom' => true,
        ]);

        $this->postJson('/request-ns/items', ['name' => 'Valid Item'])
            ->assertCreated();

        $this->assertDatabaseHas('items', ['name' => 'Valid Item']);
    }

    #[Test]
    public function request_namespace_method_overrides_config_validation(): void
    {
        config([
            'automatic-crud.configs.default.namespaces.request' => 'Wrong\Requests',
            'automatic-crud.configs.default.requests.force_custom' => true,
        ]);

        $this->postJson('/request-ns/items', [])
            ->assertUnprocessable()
            ->assertInvalid(['name' => 'The name field is required.']);
    }
}
