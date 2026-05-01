<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Support\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;

/**
 * @mixin Item
 */
class ItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
