<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature\Controllers\Index;

use JG\LaravelAutomaticCrud\Tests\Feature\CrudTestCase;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\SearchableItemController;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use JG\LaravelAutomaticCrud\Tests\Support\Resources\SearchableItemResource;
use PHPUnit\Framework\Attributes\Test;

final class SearchTest extends CrudTestCase
{
    protected function defineRoutes($router): void
    {
        $router->get('/searchable-items', [SearchableItemController::class, 'index']);
    }

    #[Test]
    public function applies_search_filter_from_searchable_interface(): void
    {
        $alpha = Item::factory()->create(['name' => 'Alpha']);
        Item::factory()->create(['name' => 'Beta']);
        Item::factory()->create(['name' => 'Gamma']);

        $this->getJson('/searchable-items?name=Alpha&pagination=false')
            ->assertOk()
            ->assertExactJson(['data' => [new SearchableItemResource($alpha)->resolve()]]);
    }

    #[Test]
    public function returns_all_results_when_no_filter_params_provided(): void
    {
        Item::factory()->count(3)->create();

        $this->getJson('/searchable-items?pagination=false')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function filter_returns_empty_when_no_match(): void
    {
        Item::factory()->create(['name' => 'Alpha']);

        $this->getJson('/searchable-items?name=Nonexistent&pagination=false')
            ->assertOk()
            ->assertExactJson(['data' => []]);
    }
}
