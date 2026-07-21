<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Support\Requests\MemoizationHooks\Items;

use Illuminate\Foundation\Http\FormRequest;

class UpdateItemRequest extends FormRequest
{
    public static int $rulesCalls = 0;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        ++self::$rulesCalls;

        return [
            'name' => ['required', 'string'],
        ];
    }
}
