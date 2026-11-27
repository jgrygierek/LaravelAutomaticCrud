<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

trait ResourceTrait
{
    /**
     * @var class-string<JsonResource>|null
     */
    private ?string $resolvedResourceClass = null;

    /**
     * @var class-string<JsonResource>|null
     */
    private ?string $resolvedResourceCollectionClass = null;

    abstract protected function getModelClass(): string;

    abstract protected function getConfig(string $key): mixed;

    protected function getResourceNamespace(): string
    {
        return $this->getConfig('namespaces.resource');
    }

    protected function getResource(Model $object): JsonResource
    {
        $resource = $this->getResourceClass();

        return new $resource($object);
    }

    protected function getCollectionResource(Collection|LengthAwarePaginator $collection): JsonResource
    {
        $resource = $this->getResourceCollectionClass();

        return $resource::collection($collection);
    }

    /**
     * @return class-string<JsonResource>
     */
    protected function getResourceClass(): string
    {
        return $this->resolvedResourceClass ??= $this->resolveResourceClass('Resource') ?? JsonResource::class;
    }

    /**
     * @return class-string<JsonResource>
     */
    protected function getResourceCollectionClass(): string
    {
        return $this->resolvedResourceCollectionClass ??= $this->resolveResourceClass('CollectionResource') ?? $this->getResourceClass();
    }

    protected function getResourceClassByConvention(string $suffix): string
    {
        return sprintf('%s\\%s%s', $this->getResourceNamespace(), Str::ucfirst(class_basename($this->getModelClass())), $suffix);
    }

    /**
     * @return class-string<JsonResource>|null
     */
    protected function resolveResourceClass(string $suffix): ?string
    {
        $class = $this->getResourceClassByConvention($suffix);

        return class_exists($class) ? $class : null;
    }
}
