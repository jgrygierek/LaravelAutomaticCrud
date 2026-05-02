<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Support\Requests\Items;

use Illuminate\Foundation\Http\FormRequest;

class UpdateItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
        ];
    }
}
