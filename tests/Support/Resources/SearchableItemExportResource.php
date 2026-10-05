<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Support\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JG\LaravelAutomaticCrud\Tests\Support\Models\SearchableItem;

/**
 * @mixin SearchableItem
 */
class SearchableItemExportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'created_at' => $this->created_at,
        ];
    }
}
