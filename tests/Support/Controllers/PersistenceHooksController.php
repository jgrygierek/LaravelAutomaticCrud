<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Support\Controllers;

use Illuminate\Database\Eloquent\Model;
use JG\LaravelAutomaticCrud\Http\Controllers\CrudableController;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;

class PersistenceHooksController extends CrudableController
{
    public static int $deleteModelCalls = 0;

    protected function getModelClass(): string
    {
        return Item::class;
    }

    protected function createModel(array $data): Model
    {
        return parent::createModel([...$data, 'secret' => 'created-hook']);
    }

    protected function updateModel(Model $model, array $data): Model
    {
        return parent::updateModel($model, [...$data, 'secret' => 'updated-hook']);
    }

    protected function deleteModel(Model $model): Model
    {
        ++self::$deleteModelCalls;

        return parent::deleteModel($model);
    }
}
