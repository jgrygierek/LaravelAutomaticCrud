<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use RuntimeException;

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

    /**
     * @var class-string<JsonResource>|null
     */
    private ?string $resolvedResourceExportClass = null;

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

    private function getCollectionResource(Collection|LengthAwarePaginator $collection): JsonResource
    {
        $resource = $this->getResourceCollectionClass();

        return $resource::collection($collection);
    }

    /**
     * @return class-string<JsonResource>
     */
    protected function getResourceClass(): string
    {
        return $this->resolvedResourceClass ??= $this->resolveResourceClass(
            $this->getResourceFileName($this->getModelClass()),
        ) ?? JsonResource::class;
    }

    /**
     * @return class-string<JsonResource>
     */
    protected function getResourceCollectionClass(): string
    {
        return $this->resolvedResourceCollectionClass ??= $this->resolveResourceClass(
            $this->getResourceCollectionFileName($this->getModelClass()),
        ) ?? $this->getResourceClass();
    }

    /**
     * @return class-string<JsonResource>
     */
    protected function getResourceExportClass(): string
    {
        if ($this->resolvedResourceExportClass === null) {
            $name = $this->getResourceExportFileName($this->getModelClass());

            $this->resolvedResourceExportClass = $this->resolveResourceClass($name)
                ?? throw new RuntimeException(sprintf('Export resource class [%s\\%s] not found.', $this->getResourceNamespace(), $name));
        }

        return $this->resolvedResourceExportClass;
    }

    /**
     * @return class-string<JsonResource>|null
     */
    private function resolveResourceClass(string $name): ?string
    {
        $class = $this->getResourceNamespace() . '\\' . $name;

        return class_exists($class) ? $class : null;
    }

    private function getResourceFileName(string $modelClass): string
    {
        return Str::ucfirst(class_basename($modelClass)) . 'Resource';
    }

    private function getResourceCollectionFileName(string $modelClass): string
    {
        return Str::ucfirst(class_basename($modelClass)) . 'CollectionResource';
    }

    private function getResourceExportFileName(string $modelClass): string
    {
        return Str::ucfirst(class_basename($modelClass)) . 'ExportResource';
    }
}
