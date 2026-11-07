<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Helpers;

use Illuminate\Validation\Rule;
use JG\LaravelAutomaticCrud\Enums\SortDirection;

final class SortingRulesHelper
{
    /**
     * @param  list<string>  $sortByFields
     * @return array<string, list<mixed>>
     */
    public static function rules(array $sortByFields): array
    {
        return [
            'sort_by' => ['nullable', 'string', Rule::in($sortByFields)],
            'sort_direction' => ['nullable', Rule::enum(SortDirection::class)],
        ];
    }
}
