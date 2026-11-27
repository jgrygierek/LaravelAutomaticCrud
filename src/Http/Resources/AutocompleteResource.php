<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Model $resource
 */
class AutocompleteResource extends JsonResource
{
    public function __construct(mixed $resource, protected readonly ?string $labelColumn = null)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'label' => $this->resource->getAttribute($this->labelColumn),
        ];
    }
}
