<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use JG\LaravelAutomaticCrud\Tests\Feature\CrudTestCase;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\ExportItemController;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\ItemController;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\SearchableItemController;
use JG\LaravelAutomaticCrud\Tests\Support\Enums\ItemKind;
use JG\LaravelAutomaticCrud\Tests\Support\Enums\ItemStatus;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use JG\LaravelAutomaticCrud\Tests\Support\Models\KeylessItem;
use PHPUnit\Framework\Attributes\Test;

final class ExportTest extends CrudTestCase
{
    protected function defineRoutes($router): void
    {
        $router->get('items/export', [ItemController::class, 'export'])->name('items.export');
        $router->get('searchable-items/export', [SearchableItemController::class, 'export']);
        $router->get('export-items/export', [ExportItemController::class, 'export']);
    }

    private function createItems(int $count): void
    {
        $now = now()->toDateTimeString();

        foreach (array_chunk(range(1, $count), 1000) as $chunk) {
            DB::table('items')->insert(array_map(static fn (int $i): array => ['name' => "Item $i", 'created_at' => $now, 'updated_at' => $now], $chunk));
        }
    }

    private function sendRequest(array $query = []): TestResponse
    {
        return $this->get(route('items.export', $query));
    }

    #[Test]
    public function exports_all_items_as_csv_using_resource_fields(): void
    {
        Item::factory()->count(15)->create();

        $response = $this->sendRequest()
            ->assertOk()
            ->assertDownload('items.csv')
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $lines = explode("\r\n", trim($response->streamedContent()));

        $this->assertCount(16, $lines);
        $this->assertSame('id,name', $lines[0]);
        $this->assertSame('1,' . Item::find(1)->name, $lines[1]);
    }

    #[Test]
    public function applies_sorting_from_query_parameters(): void
    {
        Item::factory()->create(['name' => 'Alpha']);
        Item::factory()->create(['name' => 'Beta']);

        $content = $this->sendRequest(['sort_by' => 'name', 'sort_direction' => 'desc'])->streamedContent();

        $this->assertSame("id,name\r\n2,Beta\r\n1,Alpha\r\n", $content);
    }

    #[Test]
    public function applies_search_filters(): void
    {
        Item::factory()->create(['name' => 'Alpha']);
        Item::factory()->create(['name' => 'Beta']);

        $lines = explode("\r\n", trim($this->get('/searchable-items/export?name=Beta')->streamedContent()));

        $this->assertCount(2, $lines);
        $this->assertSame('id,name,created_at', $lines[0]);
        $this->assertStringStartsWith('2,Beta,', $lines[1]);
    }

    #[Test]
    public function adds_primary_key_as_tie_breaker_when_query_is_sorted(): void
    {
        Item::factory()->count(2)->create(['name' => 'Same']);
        DB::enableQueryLog();

        $this->sendRequest(['sort_by' => 'name'])->streamedContent();

        $this->assertStringContainsString('order by "name" asc, "items"."id" asc', (string) DB::getQueryLog()[0]['query']);
    }

    #[Test]
    public function groups_existing_where_clauses_before_chunking_by_primary_key(): void
    {
        $this->app->instance(ExportItemController::class, new class extends ExportItemController
        {
            protected function getExportChunkSize(): int
            {
                return 1;
            }

            protected function modifyQuery(Builder $query): Builder
            {
                return $query->where('name', 'Alpha')->orWhere('name', 'Beta');
            }
        });

        Item::factory()->create(['name' => 'Alpha']);
        Item::factory()->create(['name' => 'Beta']);
        Item::factory()->create(['name' => 'Gamma']);
        DB::enableQueryLog();

        $content = $this->get('/export-items/export')->streamedContent();

        $this->assertSame("id,name\r\n1,Alpha\r\n2,Beta\r\n", $content);
        $this->assertStringContainsString('where ("name" = ? or "name" = ?) and "items"."id" > ?', (string) DB::getQueryLog()[1]['query']);
        $this->assertSame(['Alpha', 'Beta', 1], DB::getQueryLog()[1]['bindings']);
    }

    #[Test]
    public function fetches_sorted_query_in_chunks_by_offset(): void
    {
        config(['automatic-crud.configs.default.export.chunk_size' => 2]);
        $this->createItems(3);
        DB::enableQueryLog();

        $content = $this->sendRequest(['sort_by' => 'name', 'sort_direction' => 'desc'])->streamedContent();

        $queries = array_column(DB::getQueryLog(), 'query');
        $this->assertSame("id,name\r\n3,\"Item 3\"\r\n2,\"Item 2\"\r\n1,\"Item 1\"\r\n", $content);
        $this->assertCount(2, $queries);
        $this->assertStringEndsWith('order by "name" desc, "items"."id" asc limit 2 offset 2', $queries[1]);
    }

    #[Test]
    public function skips_tie_breaker_for_model_without_primary_key(): void
    {
        $this->app->instance(ExportItemController::class, new class extends ExportItemController
        {
            protected function getModelClass(): string
            {
                return KeylessItem::class;
            }

            protected function modifyQuery(Builder $query): Builder
            {
                return $query->orderBy('name');
            }

            protected function getResourceExportClass(): string
            {
                return JsonResource::class;
            }

            protected function getExportRow(Model $model, Request $request): array
            {
                return ['name' => $model->name];
            }
        });

        Item::factory()->create(['name' => 'Beta']);
        Item::factory()->create(['name' => 'Alpha']);

        $this->assertSame("name\r\nAlpha\r\nBeta\r\n", $this->get('/export-items/export')->assertOk()->streamedContent());
    }

    #[Test]
    public function aligns_rows_with_differing_keys_to_header_of_first_row(): void
    {
        $this->app->instance(ExportItemController::class, new class extends ExportItemController
        {
            protected function getExportRow(Model $model, Request $request): array
            {
                return $model->secret === null
                    ? ['id' => $model->id, 'name' => $model->name, 'extra' => 'dropped']
                    : ['name' => $model->name, 'secret' => $model->secret, 'id' => $model->id];
            }
        });

        Item::factory()->create(['name' => 'A', 'secret' => 'x']);
        Item::factory()->create(['name' => 'B', 'secret' => null]);

        $this->assertSame("name,secret,id\r\nA,x,1\r\nB,,2\r\n", $this->get('/export-items/export')->streamedContent());
    }

    #[Test]
    public function uses_custom_header_labels_and_selects_columns_by_header_keys(): void
    {
        $this->app->instance(ExportItemController::class, new class extends ExportItemController
        {
            protected function getExportHeader(array $row): array
            {
                return ['name' => 'Name', 'missing' => 'Missing', 'id' => 'ID'];
            }
        });

        Item::factory()->create(['name' => 'Alpha']);

        $this->assertSame("Name,Missing,ID\r\nAlpha,,1\r\n", $this->get('/export-items/export')->streamedContent());
    }

    #[Test]
    public function uses_custom_header_list_as_both_keys_and_labels(): void
    {
        $this->app->instance(ExportItemController::class, new class extends ExportItemController
        {
            protected function getExportHeader(array $row): array
            {
                return ['name', 'id'];
            }
        });

        Item::factory()->create(['name' => 'Alpha']);

        $this->assertSame("name,id\r\nAlpha,1\r\n", $this->get('/export-items/export')->streamedContent());
    }

    #[Test]
    public function drops_row_columns_missing_from_custom_header(): void
    {
        $this->app->instance(ExportItemController::class, new class extends ExportItemController
        {
            protected function getExportHeader(array $row): array
            {
                return ['name'];
            }
        });

        Item::factory()->create(['name' => 'Alpha']);

        $this->assertSame("name\r\nAlpha\r\n", $this->get('/export-items/export')->streamedContent());
    }

    #[Test]
    public function writes_custom_header_when_there_are_no_items(): void
    {
        $this->app->instance(ExportItemController::class, new class extends ExportItemController
        {
            protected function getExportHeader(array $row): array
            {
                return ['name' => 'Name', 'missing' => 'Missing', 'id' => 'ID'];
            }
        });

        $this->assertSame("Name,Missing,ID\r\n", $this->get('/export-items/export')->assertOk()->streamedContent());
    }

    #[Test]
    public function exports_all_items_fetched_in_chunks_of_5000_by_default(): void
    {
        $this->createItems(5001);
        DB::enableQueryLog();

        $lines = explode("\r\n", trim($this->sendRequest()->streamedContent()));

        $queries = array_column(DB::getQueryLog(), 'query');
        $this->assertCount(5002, $lines);
        $this->assertSame('5001,"Item 5001"', $lines[5001]);
        $this->assertCount(2, $queries);
        $this->assertStringEndsWith('order by "items"."id" asc limit 5000', $queries[0]);
        $this->assertStringContainsString('where "items"."id" > ? order by "items"."id" asc limit 5000', (string) $queries[1]);
    }

    #[Test]
    public function uses_configured_chunk_size_given_as_string(): void
    {
        config(['automatic-crud.configs.default.export.chunk_size' => '2']);
        $this->createItems(5);
        DB::enableQueryLog();

        $content = $this->sendRequest()->streamedContent();

        $queries = array_column(DB::getQueryLog(), 'query');
        $this->assertSame("id,name\r\n1,\"Item 1\"\r\n2,\"Item 2\"\r\n3,\"Item 3\"\r\n4,\"Item 4\"\r\n5,\"Item 5\"\r\n", $content);
        $this->assertCount(3, $queries);
        $this->assertStringEndsWith('limit 2', $queries[2]);
        $this->assertSame([4], DB::getQueryLog()[2]['bindings']);
    }

    #[Test]
    public function controller_chunk_size_override_takes_precedence_over_config(): void
    {
        $this->app->instance(ExportItemController::class, new class extends ExportItemController
        {
            protected function getExportChunkSize(): int
            {
                return 2;
            }
        });

        config(['automatic-crud.configs.default.export.chunk_size' => 1]);
        $this->createItems(5);
        DB::enableQueryLog();

        $lines = explode("\r\n", trim($this->get('/export-items/export')->streamedContent()));

        $this->assertCount(6, $lines);
        $this->assertCount(3, DB::getQueryLog());
    }

    #[Test]
    public function returns_empty_file_when_there_are_no_items(): void
    {
        $this->assertSame('', $this->sendRequest()->assertOk()->streamedContent());
    }

    #[Test]
    public function uses_overridden_file_name_and_row_and_normalizes_non_scalar_values(): void
    {
        $this->app->instance(ExportItemController::class, new class extends ExportItemController
        {
            protected function getExportFileName(): string
            {
                return 'custom-items.csv';
            }

            protected function getExportRow(Model $model, Request $request): array
            {
                return [
                    'name' => $model->name,
                    'label' => Str::of($model->name)->lower(),
                    'tags' => ['ą', 'b/c'],
                    'meta' => (object) ['id' => $model->id],
                    'status' => ItemStatus::Active,
                    'kind' => ItemKind::Physical,
                ];
            }
        });

        Item::factory()->count(3)->sequence(['name' => 'A'], ['name' => 'B'], ['name' => 'C'])->create();

        $content = $this->get('/export-items/export')
            ->assertDownload('custom-items.csv')
            ->streamedContent();

        $this->assertSame(
            "name,label,tags,meta,status,kind\r\n"
            . "A,a,\"[\"\"ą\"\",\"\"b/c\"\"]\",\"{\"\"id\"\":1}\",active,Physical\r\n"
            . "B,b,\"[\"\"ą\"\",\"\"b/c\"\"]\",\"{\"\"id\"\":2}\",active,Physical\r\n"
            . "C,c,\"[\"\"ą\"\",\"\"b/c\"\"]\",\"{\"\"id\"\":3}\",active,Physical\r\n",
            $content,
        );
    }

    #[Test]
    public function writes_scalar_values_as_is_and_booleans_as_integers(): void
    {
        $this->app->instance(ExportItemController::class, new class extends ExportItemController
        {
            protected function getResourceExportClass(): string
            {
                return JsonResource::class;
            }

            protected function getExportRow(Model $model, Request $request): array
            {
                return [
                    'equals' => '=SUM(A1)',
                    'plus' => '+x',
                    'minus' => '-x',
                    'at' => '@x',
                    'tab' => "\tx",
                    'return' => "\rx",
                    'numeric' => '-5.00',
                    'integer' => -5,
                    'plain' => 'x',
                    'empty' => '',
                    'true' => true,
                    'false' => false,
                ];
            }
        });

        Item::factory()->create();

        $lines = explode("\r\n", $this->get('/export-items/export')->streamedContent(), 2);

        $this->assertSame("=SUM(A1),+x,-x,@x,\"\tx\",\"\rx\",-5.00,-5,x,,1,0\r\n", $lines[1]);
    }

    #[Test]
    public function returns_422_when_form_request_validation_fails(): void
    {
        $this->getJson(route('items.export', ['sort_direction' => 'sideways']))
            ->assertUnprocessable()
            ->assertInvalid(['sort_direction']);
    }

    #[Test]
    public function returns_500_before_streaming_when_query_fails(): void
    {
        Schema::drop('items');

        $this->sendRequest()->assertInternalServerError();
    }
}
