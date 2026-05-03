<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Unit;

use JG\LaravelAutomaticCrud\Tests\Support\Models\SearchableItemWithConfigFilters;
use JG\LaravelAutomaticCrud\Tests\Support\SearchTraitController;
use JG\LaravelAutomaticCrud\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class SearchTraitConfigFiltersTest extends TestCase
{
    #[Test]
    public function build_query_applies_default_config_filters_for_keyed_searchable_model(): void
    {
        request()->merge(['name' => 'Alpha']);

        $controller = new SearchTraitController(SearchableItemWithConfigFilters::class, 'default');
        $wheres = $controller->exposeBuildQuery()->getQuery()->wheres;

        $this->assertNotNull($wheres);
        $this->assertSame('name', $wheres[0]['column']);
        $this->assertSame('Alpha', $wheres[0]['value']);
    }

    #[Test]
    public function build_query_applies_custom_config_filters_for_keyed_searchable_model(): void
    {
        request()->merge(['id' => '1']);

        $controller = new SearchTraitController(SearchableItemWithConfigFilters::class, 'custom');
        $wheres = $controller->exposeBuildQuery()->getQuery()->wheres;

        $this->assertNotNull($wheres);
        $this->assertSame('id', $wheres[0]['column']);
        $this->assertSame('1', $wheres[0]['value']);
    }

    #[Test]
    public function build_query_falls_back_to_default_filters_when_config_not_found(): void
    {
        request()->merge(['name' => 'Alpha']);

        $controller = new SearchTraitController(SearchableItemWithConfigFilters::class, 'nonexistent');
        $wheres = $controller->exposeBuildQuery()->getQuery()->wheres;

        $this->assertNotNull($wheres);
        $this->assertSame('name', $wheres[0]['column']);
        $this->assertSame('Alpha', $wheres[0]['value']);
    }

}
