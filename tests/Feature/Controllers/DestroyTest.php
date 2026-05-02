<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature\Controllers;

use Illuminate\Testing\TestResponse;
use JG\LaravelAutomaticCrud\Tests\Feature\CrudTestCase;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use PHPUnit\Framework\Attributes\Test;

final class DestroyTest extends CrudTestCase
{
    private function sendRequest(Item|int $item): TestResponse
    {
        return $this->deleteJson(route('items.destroy', $item));
    }

    #[Test]
    public function deletes_item_and_returns_204(): void
    {
        $item = Item::factory()->create();

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
