<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use JG\LaravelAutomaticCrud\Tests\Feature\CrudTestCase;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\AutocompleteItemController;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use JG\LaravelAutomaticCrud\Tests\Support\Models\KeylessItem;
use JG\LaravelAutomaticCrud\Tests\Support\Models\SearchableItem;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;

final class AutocompleteTest extends CrudTestCase
{
    protected function defineRoutes($router): void
    {
        $router->get('items/autocomplete', [AutocompleteItemController::class, 'autocomplete']);
    }

    private function sendRequest(array $query = []): TestResponse
    {
        return $this->getJson('/items/autocomplete?' . http_build_query($query));
    }

    private function bindController(AutocompleteItemController $controller): void
    {
        $this->app->instance(AutocompleteItemController::class, $controller);
    }

    #[Test]
    public function returns_items_matching_term_in_any_column_using_autocomplete_resource(): void
    {
        Item::factory()->create(['name' => 'Laptop']);
        Item::factory()->create(['name' => 'Desk', 'secret' => 'laptop stand']);
        Item::factory()->create(['name' => 'Chair']);

        $this->sendRequest(['q' => 'lapt'])
            ->assertOk()
            ->assertExactJson(['data' => [
                ['id' => 2, 'label' => 'Desk'],
                ['id' => 1, 'label' => 'Laptop'],
            ]]);
    }

    #[Test]
    public function limits_results_to_configured_limit(): void
    {
        config(['automatic-crud.configs.default.autocomplete.limit' => 3]);
        Item::factory()->count(5)->create(['name' => 'Laptop']);

        $this->sendRequest(['q' => 'lap'])
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function limits_results_to_ten_by_default(): void
    {
        Item::factory()->count(12)->create(['name' => 'Laptop']);

        $this->sendRequest(['q' => 'lap'])
            ->assertOk()
            ->assertJsonCount(10, 'data');
    }

    #[Test]
    #[TestWith([0])]
    #[TestWith([-1])]
    public function limits_results_to_one_when_configured_limit_is_lower(int $limit): void
    {
        config(['automatic-crud.configs.default.autocomplete.limit' => $limit]);
        Item::factory()->count(3)->create(['name' => 'Laptop']);

        $this->sendRequest(['q' => 'lap'])
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function limits_results_using_overridden_limit(): void
    {
        $this->bindController(new class extends AutocompleteItemController
        {
            protected function getAutocompleteLimit(): int
            {
                return 2;
            }
        });
        Item::factory()->count(5)->create(['name' => 'Laptop']);

        $this->sendRequest(['q' => 'lap'])
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function does_not_count_matching_items(): void
    {
        Item::factory()->create(['name' => 'Laptop']);
        DB::enableQueryLog();

        $this->sendRequest(['q' => 'lap'])->assertOk();

        $this->assertCount(1, DB::getQueryLog());
        $this->assertStringContainsString('limit 10', (string) DB::getQueryLog()[0]['query']);
    }

    #[Test]
    public function searches_when_term_length_equals_min_length(): void
    {
        Item::factory()->create(['name' => 'Laptop']);

        $this->sendRequest(['q' => 'lap'])
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function counts_multibyte_characters_when_checking_min_length(): void
    {
        Item::factory()->create(['name' => 'Łódź']);

        $this->sendRequest(['q' => 'Łó'])
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    #[Test]
    public function returns_empty_list_without_querying_database_when_term_is_shorter_than_min_length(): void
    {
        Item::factory()->create(['name' => 'Laptop']);
        DB::enableQueryLog();

        $this->sendRequest(['q' => 'la'])
            ->assertOk()
            ->assertExactJson(['data' => []]);

        $this->assertSame([], DB::getQueryLog());
    }

    #[Test]
    public function returns_empty_list_when_term_is_missing(): void
    {
        Item::factory()->create(['name' => 'Laptop']);

        $this->sendRequest()
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    #[Test]
    public function returns_empty_list_when_term_is_not_a_string(): void
    {
        Item::factory()->create(['name' => 'Laptop']);

        $this->sendRequest(['q' => ['lap', 'top']])
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    #[Test]
    public function returns_items_with_empty_columns_when_min_length_is_zero_and_term_is_empty(): void
    {
        config(['automatic-crud.configs.default.autocomplete.min_length' => 0]);
        $this->bindController(new class extends AutocompleteItemController
        {
            protected function getAutocompleteColumns(): array
            {
                return ['secret'];
            }
        });
        Item::factory()->create(['name' => 'Laptop']);
        Item::factory()->create(['name' => 'Desk']);

        $this->sendRequest()
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function uses_min_length_from_named_config(): void
    {
        config(['automatic-crud.configs.short.autocomplete.min_length' => 1]);
        $this->bindController(new class extends AutocompleteItemController
        {
            protected function getConfigName(): string
            {
                return 'short';
            }
        });
        Item::factory()->create(['name' => 'Laptop']);

        $this->sendRequest(['q' => 'l'])
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function reads_term_from_configured_parameter(): void
    {
        config(['automatic-crud.configs.default.autocomplete.term_parameter' => 'search']);
        Item::factory()->create(['name' => 'Laptop']);
        Item::factory()->create(['name' => 'Desk']);

        $this->sendRequest(['search' => 'lap'])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.label', 'Laptop');

        $this->sendRequest(['q' => 'lap'])
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    #[Test]
    public function reads_term_from_overridden_parameter(): void
    {
        $this->bindController(new class extends AutocompleteItemController
        {
            protected function getAutocompleteTermParameter(): string
            {
                return 'phrase';
            }
        });
        Item::factory()->create(['name' => 'Laptop']);

        $this->sendRequest(['phrase' => 'lap'])
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function treats_like_wildcards_in_term_literally(): void
    {
        Item::factory()->create(['name' => '100% cotton']);
        Item::factory()->create(['name' => '1000 cotton']);
        Item::factory()->create(['name' => 'snake_case']);
        Item::factory()->create(['name' => 'snakeXcase']);
        Item::factory()->create(['name' => 'Hey! You']);
        Item::factory()->create(['name' => 'Hey You']);

        $this->sendRequest(['q' => '00%'])->assertJsonCount(1, 'data')->assertJsonPath('data.0.label', '100% cotton');
        $this->sendRequest(['q' => 'e_c'])->assertJsonCount(1, 'data')->assertJsonPath('data.0.label', 'snake_case');
        $this->sendRequest(['q' => 'ey!'])->assertJsonCount(1, 'data')->assertJsonPath('data.0.label', 'Hey! You');
    }

    #[Test]
    public function keeps_modify_query_conditions_when_term_matches_another_column(): void
    {
        $this->bindController(new class extends AutocompleteItemController
        {
            protected function modifyQuery(Builder $query): Builder
            {
                return $query->where('name', '!=', 'Hidden');
            }
        });
        Item::factory()->create(['name' => 'Visible', 'secret' => 'laptop']);
        Item::factory()->create(['name' => 'Hidden', 'secret' => 'laptop']);

        $this->sendRequest(['q' => 'lap'])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.label', 'Visible');
    }

    #[Test]
    public function keeps_term_condition_when_modify_query_adds_or_where(): void
    {
        $this->bindController(new class extends AutocompleteItemController
        {
            protected function modifyQuery(Builder $query): Builder
            {
                return $query->where('name', 'Desk')->orWhere('secret', 'public');
            }
        });
        Item::factory()->create(['name' => 'Desk']);
        Item::factory()->create(['name' => 'Chair', 'secret' => 'public']);
        Item::factory()->create(['name' => 'Laptop', 'secret' => 'public']);

        $this->sendRequest(['q' => 'lap'])
            ->assertOk()
            ->assertExactJson(['data' => [['id' => 3, 'label' => 'Laptop']]]);
    }

    #[Test]
    public function qualifies_columns_with_model_table(): void
    {
        $this->bindController(new class extends AutocompleteItemController
        {
            protected function modifyQuery(Builder $query): Builder
            {
                return $query->select('items.*')->join('items as others', 'others.id', '=', 'items.id');
            }
        });
        Item::factory()->create(['name' => 'Laptop']);

        $this->sendRequest(['q' => 'lap'])
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function applies_sorting_from_query_parameters(): void
    {
        Item::factory()->create(['name' => 'Laptop A']);
        Item::factory()->create(['name' => 'Laptop B']);

        $this->sendRequest(['q' => 'lap', 'sort_by' => 'name', 'sort_direction' => 'desc'])
            ->assertOk()
            ->assertJsonPath('data.0.label', 'Laptop B')
            ->assertJsonPath('data.1.label', 'Laptop A');
    }

    #[Test]
    public function orders_by_label_column_then_primary_key_by_default(): void
    {
        Item::factory()->create(['name' => 'Laptop stand']);
        Item::factory()->create(['name' => 'Laptop']);
        Item::factory()->create(['name' => 'Laptop']);
        DB::enableQueryLog();

        $this->sendRequest(['q' => 'lap'])
            ->assertOk()
            ->assertExactJson(['data' => [
                ['id' => 2, 'label' => 'Laptop'],
                ['id' => 3, 'label' => 'Laptop'],
                ['id' => 1, 'label' => 'Laptop stand'],
            ]]);

        $this->assertStringEndsWith('order by "items"."name" asc, "items"."id" asc limit 10', DB::getQueryLog()[0]['query']);
    }

    #[Test]
    public function skips_default_order_when_sorting_is_requested(): void
    {
        Item::factory()->create(['name' => 'Laptop']);
        DB::enableQueryLog();

        $this->sendRequest(['q' => 'lap', 'sort_by' => 'name', 'sort_direction' => 'desc'])->assertOk();

        $this->assertStringEndsWith('order by "name" desc limit 10', array_last(DB::getQueryLog())['query']);
    }

    #[Test]
    public function autocomplete_sortable_columns_override_takes_precedence_over_index_sortable_columns(): void
    {
        $this->bindController(new class extends AutocompleteItemController
        {
            protected function getIndexSortableColumns(): array
            {
                return ['id'];
            }

            protected function getAutocompleteSortableColumns(): array
            {
                return ['name'];
            }
        });
        Item::factory()->create(['name' => 'Laptop A']);
        Item::factory()->create(['name' => 'Laptop B']);

        $this->sendRequest(['q' => 'lap', 'sort_by' => 'name', 'sort_direction' => 'desc'])
            ->assertOk()
            ->assertJsonPath('data.0.label', 'Laptop B');
    }

    #[Test]
    public function orders_by_label_column_only_for_model_without_primary_key(): void
    {
        $this->bindController(new class extends AutocompleteItemController
        {
            protected function getModelClass(): string
            {
                return KeylessItem::class;
            }
        });
        Item::factory()->create(['name' => 'Laptop']);
        DB::enableQueryLog();

        $this->sendRequest(['q' => 'lap'])->assertOk();

        $this->assertStringEndsWith('order by "items"."name" asc limit 10', DB::getQueryLog()[0]['query']);
    }

    #[Test]
    public function orders_by_primary_key_only_when_no_columns_are_configured(): void
    {
        $this->bindController(new class extends AutocompleteItemController
        {
            protected function getAutocompleteColumns(): array
            {
                return [];
            }
        });
        Item::factory()->create(['name' => 'Laptop']);
        DB::enableQueryLog();

        $this->sendRequest(['q' => 'lap'])->assertOk();

        $this->assertStringEndsWith('order by "items"."id" asc limit 10', DB::getQueryLog()[0]['query']);
    }

    #[Test]
    public function applies_search_filters(): void
    {
        $this->bindController(new class extends AutocompleteItemController
        {
            protected function getModelClass(): string
            {
                return SearchableItem::class;
            }
        });
        Item::factory()->create(['name' => 'Laptop A']);
        Item::factory()->create(['name' => 'Laptop B']);

        $this->sendRequest(['q' => 'lap', 'name' => 'Laptop B'])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.label', 'Laptop B');
    }

    #[Test]
    public function skips_column_search_when_no_columns_are_configured(): void
    {
        $this->bindController(new class extends AutocompleteItemController
        {
            protected function getAutocompleteColumns(): array
            {
                return [];
            }
        });
        Item::factory()->create(['name' => 'Laptop']);
        Item::factory()->create(['name' => 'Desk']);

        $this->sendRequest(['q' => 'lap'])
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function returns_items_without_term_when_no_columns_are_configured(): void
    {
        $this->bindController(new class extends AutocompleteItemController
        {
            protected function getModelClass(): string
            {
                return SearchableItem::class;
            }

            protected function getAutocompleteColumns(): array
            {
                return [];
            }

            protected function getAutocompleteLabelColumn(): string
            {
                return 'name';
            }
        });
        Item::factory()->create(['name' => 'Laptop']);
        Item::factory()->create(['name' => 'Desk']);

        $this->sendRequest(['name' => 'Laptop'])
            ->assertOk()
            ->assertExactJson(['data' => [['id' => 1, 'label' => 'Laptop']]]);
    }

    #[Test]
    public function uses_overridden_search(): void
    {
        $this->bindController(new class extends AutocompleteItemController
        {
            protected function applyAutocompleteSearch(Builder $query, string $term): Builder
            {
                return $query->where('name', 'like', $term . '%');
            }
        });
        Item::factory()->create(['name' => 'Laptop']);
        Item::factory()->create(['name' => 'Flap']);

        $this->sendRequest(['q' => 'lap'])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.label', 'Laptop');
    }

    #[Test]
    public function uses_package_resource_with_first_column_as_label_when_autocomplete_resource_is_missing(): void
    {
        $this->bindController(new class extends AutocompleteItemController
        {
            protected function getResourceNamespace(): string
            {
                return 'JG\LaravelAutomaticCrud\Tests\Support\Resources\WithCollection';
            }

            protected function getAutocompleteColumns(): array
            {
                return ['secret', 'name'];
            }
        });
        Item::factory()->create(['name' => 'Bag', 'secret' => 'Laptop bag']);

        $this->sendRequest(['q' => 'lap'])
            ->assertOk()
            ->assertExactJson(['data' => [['id' => 1, 'label' => 'Laptop bag']]]);
    }

    #[Test]
    public function uses_first_column_as_label_when_columns_are_not_a_list(): void
    {
        $this->bindController(new class extends AutocompleteItemController
        {
            protected function getResourceNamespace(): string
            {
                return 'JG\LaravelAutomaticCrud\Tests\Support\Resources\WithCollection';
            }

            protected function getAutocompleteColumns(): array
            {
                return [1 => 'secret', 2 => 'name'];
            }
        });
        Item::factory()->create(['name' => 'Bag', 'secret' => 'Laptop bag']);

        $this->sendRequest(['q' => 'lap'])
            ->assertOk()
            ->assertExactJson(['data' => [['id' => 1, 'label' => 'Laptop bag']]]);
    }

    #[Test]
    public function uses_package_resource_with_overridden_label_column(): void
    {
        $this->bindController(new class extends AutocompleteItemController
        {
            protected function getResourceNamespace(): string
            {
                return 'JG\LaravelAutomaticCrud\Tests\Support\Resources\WithCollection';
            }

            protected function getAutocompleteLabelColumn(): string
            {
                return 'secret';
            }
        });
        Item::factory()->create(['name' => 'Laptop', 'secret' => 'Hidden label']);

        $this->sendRequest(['q' => 'lap'])
            ->assertOk()
            ->assertExactJson(['data' => [['id' => 1, 'label' => 'Hidden label']]]);
    }

    #[Test]
    public function passes_label_column_to_autocomplete_resource_subclass(): void
    {
        $this->bindController(new class extends AutocompleteItemController
        {
            protected function getResourceNamespace(): string
            {
                return 'JG\LaravelAutomaticCrud\Tests\Support\Resources\WithAutocomplete';
            }
        });
        Item::factory()->create(['name' => 'Laptop']);

        $this->sendRequest(['q' => 'lap'])
            ->assertOk()
            ->assertExactJson(['data' => [['id' => 1, 'label' => 'Laptop', 'type' => 'custom']]]);
    }

    #[Test]
    public function throws_when_package_resource_has_no_label_column(): void
    {
        $this->withoutExceptionHandling();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/^Autocomplete label column for \[.+\] is not defined\.$/');
        $this->bindController(new class extends AutocompleteItemController
        {
            protected function getResourceNamespace(): string
            {
                return 'JG\LaravelAutomaticCrud\Tests\Support\Resources\WithCollection';
            }

            protected function getAutocompleteColumns(): array
            {
                return [];
            }
        });

        $this->sendRequest(['q' => 'lap']);
    }

    #[Test]
    public function returns_422_when_sorting_by_column_outside_index_sortable_columns(): void
    {
        $this->bindController(new class extends AutocompleteItemController
        {
            protected function getIndexSortableColumns(): array
            {
                return ['id'];
            }
        });

        $this->sendRequest(['q' => 'lap', 'sort_by' => 'name'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sort_by');
    }

    #[Test]
    public function returns_422_when_request_validation_fails(): void
    {
        $this->bindController(new class extends AutocompleteItemController
        {
            protected function getRequestNamespace(): string
            {
                return 'JG\LaravelAutomaticCrud\Tests\Support\Requests\Autocomplete';
            }
        });

        $this->sendRequest(['q' => str_repeat('a', 21)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');
    }
}
