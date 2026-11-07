<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Unit;

use Illuminate\Support\Facades\Validator;
use JG\LaravelAutomaticCrud\Helpers\SortingRulesHelper;
use JG\LaravelAutomaticCrud\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class SortingRulesHelperTest extends TestCase
{
    #[Test]
    #[DataProvider('validInputProvider')]
    public function accepts_valid_sorting_input(array $input): void
    {
        $validator = Validator::make($input, SortingRulesHelper::rules(['name', 'created_at']));

        $this->assertTrue($validator->passes());
    }

    public static function validInputProvider(): iterable
    {
        yield 'no sorting' => [[]];
        yield 'allowed column only' => [['sort_by' => 'name']];
        yield 'allowed column ascending' => [['sort_by' => 'created_at', 'sort_direction' => 'asc']];
        yield 'allowed column descending' => [['sort_by' => 'name', 'sort_direction' => 'desc']];
        yield 'empty column and direction' => [['sort_by' => null, 'sort_direction' => null]];
    }

    #[Test]
    #[DataProvider('invalidInputProvider')]
    public function rejects_invalid_sorting_input(array $input, string $invalidField): void
    {
        $validator = Validator::make($input, SortingRulesHelper::rules(['name', 'created_at']));

        $this->assertTrue($validator->fails());
        $this->assertSame([$invalidField], $validator->errors()->keys());
    }

    public static function invalidInputProvider(): iterable
    {
        yield 'column not allowed' => [['sort_by' => 'secret'], 'sort_by'];
        yield 'column as array' => [['sort_by' => ['name']], 'sort_by'];
        yield 'unknown direction' => [['sort_by' => 'name', 'sort_direction' => 'sideways'], 'sort_direction'];
        yield 'direction as array' => [['sort_by' => 'name', 'sort_direction' => ['desc']], 'sort_direction'];
    }
}
