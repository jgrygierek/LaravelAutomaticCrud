<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Support\Resources\WithAutocomplete;

use Illuminate\Http\Request;
use JG\LaravelAutomaticCrud\Http\Resources\AutocompleteResource;

class ItemAutocompleteResource extends AutocompleteResource
{
    public bool $preserveKeys = true;

    public function toArray(Request $request): array
    {
        return [...parent::toArray($request), 'type' => 'custom'];
    }
}
