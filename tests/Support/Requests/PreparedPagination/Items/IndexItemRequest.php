<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Support\Requests\PreparedPagination\Items;

use Illuminate\Foundation\Http\FormRequest;

class IndexItemRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('limit')) {
            $this->merge(['per_page' => $this->input('limit')]);
        }

        if ($this->has('all')) {
            $this->merge(['pagination' => 'false']);
        }

        if ($this->filled('p')) {
            $this->merge(['page' => $this->input('p')]);
        }
    }
}
