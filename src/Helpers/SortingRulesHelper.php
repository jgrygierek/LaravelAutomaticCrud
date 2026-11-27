<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Helpers;

use Deprecated;
use Illuminate\Validation\Rule;
use JG\LaravelAutomaticCrud\Enums\SortDirection;

final class SortingRulesHelper
{
    /**
     * @param  list<string>  $sortByFields
     * @return array<string, list<mixed>>
     */
    #[Deprecated('sort_by and sort_direction are validated by the controller, see get{Action}SortableColumns(); will be removed in 2.0.0', since: '1.6.0')]
    public static function rules(array $sortByFields): array
    {
        return [
            'sort_by' => ['nullable', 'string', Rule::in($sortByFields)],
            'sort_direction' => ['nullable', Rule::enum(SortDirection::class)],
        ];
    }
}
