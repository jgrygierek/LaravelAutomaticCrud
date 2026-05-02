<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature\Requests;

use Illuminate\Testing\TestResponse;
use JG\LaravelAutomaticCrud\Tests\Feature\CrudTestCase;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\SimpleItemController;
use JG\LaravelAutomaticCrud\Tests\Support\Models\SimpleItem;
use PHPUnit\Framework\Attributes\Test;

final class DestroyWithoutRequestTest extends CrudTestCase
{
    protected function defineRoutes($router): void
    {
        $router->apiResource('simple-items', SimpleItemController::class);
    }

    private function sendRequest(SimpleItem|int $item): TestResponse
    {
        return $this->deleteJson(route('simple-items.destroy', $item));
    }

    #[Test]
    public function deletes_item_and_returns_204(): void
    {
        $item = SimpleItem::create(['name' => 'Test Item']);

        $this->sendRequest($item)
            ->assertNoContent();

        $this->assertDatabaseMissing('items', ['id' => $item->id]);
    }

    #[Test]
    public function returns_404_for_missing_item(): void
    {
        $this->sendRequest(999)
            ->assertNotFound();
    }
}
