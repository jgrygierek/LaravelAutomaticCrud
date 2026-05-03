<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature\Controllers\Index;

use JG\LaravelAutomaticCrud\Tests\Feature\CrudTestCase;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use JG\LaravelAutomaticCrud\Tests\Support\Resources\ItemResource;
use PHPUnit\Framework\Attributes\Test;

final class BasicTest extends CrudTestCase
{
    #[Test]
    public function returns_paginated_list(): void
    {
        $items = Item::factory()->count(3)->create();

        $response = $this->getJson('/items')->assertOk();

        $response->assertJsonCount(3, 'data');
        $this->assertEquals(
            $items->map(fn (Item $item) => new ItemResource($item)->resolve())->toArray(),
            $response->json('data'),
        );
    }

    #[Test]
    public function returns_empty_list(): void
    {
        $this->getJson('/items')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
