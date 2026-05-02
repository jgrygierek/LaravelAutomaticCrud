<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature\Requests;

use Illuminate\Testing\TestResponse;
use JG\LaravelAutomaticCrud\Tests\Feature\CrudTestCase;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\SimpleItemController;
use JG\LaravelAutomaticCrud\Tests\Support\Models\SimpleItem;
use PHPUnit\Framework\Attributes\Test;

final class UpdateWithoutRequestTest extends CrudTestCase
{
    protected function defineRoutes($router): void
    {
        $router->apiResource('simple-items', SimpleItemController::class);
    }

    private function sendRequest(SimpleItem|int $item, array $data = []): TestResponse
    {
        return $this->putJson(route('simple-items.update', $item), $data);
    }

    #[Test]
    public function modifies_item(): void
    {
        $item = SimpleItem::create(['name' => 'Old Name']);

        $this->sendRequest($item, ['name' => 'New Name', 'secret' => 'sensitive'])
            ->assertOk();

        $this->assertDatabaseHas('items', ['id' => $item->id, 'name' => 'New Name', 'secret' => 'sensitive']);
    }

    #[Test]
    public function returns_404_for_missing_item(): void
    {
        $this->sendRequest(999, ['name' => 'New Name'])
            ->assertNotFound();
    }
}
