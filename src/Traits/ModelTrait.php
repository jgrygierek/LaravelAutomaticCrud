<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use RuntimeException;

trait ModelTrait
{
    abstract protected function getConfig(string $key): mixed;

    protected function getModelNamespace(): string
    {
        return $this->getConfig('namespaces.model');
    }

    /**
     * @return class-string<Model>
     */
    protected function getModelClass(): string
    {
        $class = sprintf(
            '%s\%s',
            $this->getModelNamespace(),
            $this->getModelFileName(class_basename($this)),
        );

        if (class_exists($class)) {
            return $class;
        }

        throw new RuntimeException("Model class [$class] not found.");
    }

    private function getModelFileName(string $modelClass): string
    {
        return Str::replace('Controller', '', Str::ucfirst($modelClass));
    }
}
