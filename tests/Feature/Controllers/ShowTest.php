<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature\Controllers;

use Illuminate\Testing\TestResponse;
use JG\LaravelAutomaticCrud\Tests\Feature\CrudTestCase;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use JG\LaravelAutomaticCrud\Tests\Support\Resources\ItemResource;
use PHPUnit\Framework\Attributes\Test;

final class ShowTest extends CrudTestCase
{
    private function sendRequest(Item|int $item): TestResponse
    {
        return $this->getJson(route('items.show', $item));
    }

    #[Test]
    public function returns_item(): void
    {
        $item = Item::factory()->create(['name' => 'Test Item']);

        $this->sendRequest($item)
            ->assertOk()
            ->assertExactJson(['data' => new ItemResource($item)->resolve()]);
    }

    #[Test]
    public function returns_404_for_missing_item(): void
    {
        $this->sendRequest(999)
            ->assertNotFound();
    }
}
