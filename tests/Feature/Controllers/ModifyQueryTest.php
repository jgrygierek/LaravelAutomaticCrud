<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature\Controllers;

use JG\LaravelAutomaticCrud\Tests\Feature\CrudTestCase;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\ModifyQueryController;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use PHPUnit\Framework\Attributes\Test;

final class ModifyQueryTest extends CrudTestCase
{
    protected function defineRoutes($router): void
    {
        $router->get('/modify-query-items', [ModifyQueryController::class, 'index']);
    }

    #[Test]
    public function modify_query_filters_results_in_index(): void
    {
        $included = Item::factory()->create(['name' => 'Included', 'secret' => null]);
        Item::factory()->create(['name' => 'Excluded', 'secret' => 'value']);

        $this->getJson('/modify-query-items?pagination=false')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $included->id);
    }

    #[Test]
    public function modify_query_returns_empty_when_all_items_are_filtered_out(): void
    {
        Item::factory()->create(['name' => 'Test', 'secret' => 'value']);

        $this->getJson('/modify-query-items?pagination=false')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
