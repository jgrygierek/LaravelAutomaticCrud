<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Support\Controllers;

use Illuminate\Http\Resources\Json\JsonResource;
use JG\LaravelAutomaticCrud\Http\Controllers\CrudableController;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;

class RequestMemoizationController extends CrudableController
{
    protected function getModelClass(): string
    {
        return Item::class;
    }

    protected function getRequestNamespace(): string
    {
        return 'JG\LaravelAutomaticCrud\Tests\Support\Requests\MemoizationHooks';
    }

    public function update(int|string $id): JsonResource
    {
        $this->applyRequest();

        return parent::update($id);
    }
}
