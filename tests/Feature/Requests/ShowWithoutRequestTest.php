<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature\Requests;

use Illuminate\Testing\TestResponse;
use JG\LaravelAutomaticCrud\Tests\Feature\CrudTestCase;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\SimpleItemController;
use JG\LaravelAutomaticCrud\Tests\Support\Models\SimpleItem;
use PHPUnit\Framework\Attributes\Test;

final class ShowWithoutRequestTest extends CrudTestCase
{
    protected function defineRoutes($router): void
    {
        $router->apiResource('simple-items', SimpleItemController::class);
    }

    private function sendRequest(SimpleItem|int $item): TestResponse
    {
        return $this->getJson(route('simple-items.show', $item));
    }

    #[Test]
    public function returns_item(): void
    {
        $item = SimpleItem::create(['name' => 'Test Item']);

        $this->sendRequest($item)
            ->assertOk()
            ->assertJsonPath('data.id', $item->id)
            ->assertJsonPath('data.name', $item->name);
    }

    #[Test]
    public function returns_404_for_missing_item(): void
    {
        $this->sendRequest(999)
            ->assertNotFound();
    }
}
