<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Support\Requests\Autocomplete\Items;

use Illuminate\Foundation\Http\FormRequest;

class AutocompleteItemRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:20'],
        ];
    }
}
