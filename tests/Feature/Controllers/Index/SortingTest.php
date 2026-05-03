<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature\Controllers\Index;

use JG\LaravelAutomaticCrud\Tests\Feature\CrudTestCase;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class SortingTest extends CrudTestCase
{
    #[Test]
    #[DataProvider('sortingProvider')]
    public function sorts_results(string $url, array $expectedNames): void
    {
        Item::factory()->create(['name' => 'Beta']);
        Item::factory()->create(['name' => 'Alpha']);
        Item::factory()->create(['name' => 'Gamma']);

        $names = collect(
            $this->getJson($url)->assertOk()->json('data'),
        )->pluck('name')->toArray();

        $this->assertSame($expectedNames, $names);
    }

    public static function sortingProvider(): iterable
    {
        yield 'ascending explicitly' => [
            '/items?sort_by=name&sort_direction=asc',
            ['Alpha', 'Beta', 'Gamma'],
        ];
        yield 'descending explicitly' => [
            '/items?sort_by=name&sort_direction=desc',
            ['Gamma', 'Beta', 'Alpha'],
        ];
        yield 'ascending by default' => [
            '/items?sort_by=name',
            ['Alpha', 'Beta', 'Gamma'],
        ];
    }
}
