<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Traits;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use RuntimeException;

trait RequestTrait
{
    private ?Request $resolvedRequest = null;

    abstract protected function getModelClass(): string;

    abstract protected function getConfig(string $key): mixed;

    protected function getRequestNamespace(): string
    {
        return $this->getConfig('namespaces.request');
    }

    protected function applyRequest(): Request
    {
        if ($this->resolvedRequest !== null) {
            return $this->resolvedRequest;
        }

        $class = $this->getRequestClass(
            Str::ucfirst(Route::getCurrentRoute()?->getActionMethod() ?: ''),
        );

        return $this->resolvedRequest = $class ? app($class) : request();
    }

    protected function getAllowedRequestValues(): array
    {
        $request = $this->applyRequest();

        if ($this->getConfig('requests.only_validated')) {
            if (!$request instanceof FormRequest) {
                throw new RuntimeException(sprintf('Request class [%s] must extend FormRequest when requests.only_validated is enabled.', $request::class));
            }

            return $request->validated();
        }

        return $request->all();
    }

    private function getRequestClass(string $action): ?string
    {
        $modelClass = $this->getModelClass();
        $class = sprintf(
            '%s\%s\%s',
            $this->getRequestNamespace(),
            $this->getRequestDirName($modelClass),
            $this->getRequestFileName($modelClass, $action),
        );

        if (class_exists($class)) {
            return $class;
        }

        if ($this->getConfig('requests.force_custom')) {
            throw new RuntimeException("Request class [$class] not found.");
        }

        return null;
    }

    private function getRequestDirName(string $modelClass): string
    {
        return Str::plural(class_basename($modelClass));
    }

    private function getRequestFileName(string $modelClass, string $action): string
    {
        return $action . class_basename($modelClass) . 'Request';
    }
}
