<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Support\Requests\Items;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use JG\LaravelAutomaticCrud\Enums\SortDirection;

class ExportItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sort_direction' => ['sometimes', Rule::enum(SortDirection::class)],
        ];
    }
}
