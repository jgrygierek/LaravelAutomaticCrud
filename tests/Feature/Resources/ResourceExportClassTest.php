<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature\Resources;

use JG\LaravelAutomaticCrud\Tests\Feature\CrudTestCase;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\ExportItemController;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use PHPUnit\Framework\Attributes\Test;

final class ResourceExportClassTest extends CrudTestCase
{
    protected function defineRoutes($router): void
    {
        $router->get('/export-items', [ExportItemController::class, 'index']);
        $router->get('/export-items/export', [ExportItemController::class, 'export']);
    }

    private function useResourceNamespace(string $namespace): void
    {
        $this->app->instance(ExportItemController::class, new class($namespace) extends ExportItemController
        {
            public function __construct(private readonly string $namespace) {}

            protected function getResourceNamespace(): string
            {
                return $this->namespace;
            }
        });
    }

    #[Test]
    public function export_uses_export_resource_class_over_collection_resource_class(): void
    {
        $this->useResourceNamespace('JG\LaravelAutomaticCrud\Tests\Support\Resources\WithExport');
        Item::factory()->create(['name' => 'Test']);

        $this->assertSame("id,name,type\r\n1,Test,export\r\n", $this->get('/export-items/export')->streamedContent());
    }

    #[Test]
    public function index_ignores_export_resource_class(): void
    {
        $this->useResourceNamespace('JG\LaravelAutomaticCrud\Tests\Support\Resources\WithExport');
        Item::factory()->create(['name' => 'Test']);

        $this->getJson('/export-items?pagination=false')
            ->assertOk()
            ->assertJsonPath('data.0.type', 'collection');
    }

    #[Test]
    public function returns_500_before_streaming_when_export_resource_is_missing(): void
    {
        $this->useResourceNamespace('JG\LaravelAutomaticCrud\Tests\Support\Resources\WithCollection');
        Item::factory()->create();

        $this->get('/export-items/export')->assertInternalServerError();
    }

    #[Test]
    public function returns_500_when_export_resource_is_missing_even_without_items(): void
    {
        $this->useResourceNamespace('JG\LaravelAutomaticCrud\Tests\Support\Resources\WithCollection');

        $this->get('/export-items/export')->assertInternalServerError();
    }
}
