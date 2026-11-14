<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use JG\LaravelAutomaticCrud\Tests\Feature\CrudTestCase;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\ItemController;
use JG\LaravelAutomaticCrud\Tests\Support\Models\SecondaryConnectionItem;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

final class ModelConnectionTransactionTest extends CrudTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::connection('secondary')->create('items', static function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('secret')->nullable();
            $table->timestamps();
        });

        $this->app->instance(ItemController::class, new class extends ItemController
        {
            protected function getModelClass(): string
            {
                return SecondaryConnectionItem::class;
            }

            protected function createModel(array $data): Model
            {
                $model = parent::createModel($data);

                throw_if($model->name === 'Failing Name', RuntimeException::class);

                return $model;
            }

            protected function updateModel(Model $model, array $data): Model
            {
                parent::updateModel($model, $data);

                throw new RuntimeException();
            }

            protected function deleteModel(Model $model): Model
            {
                parent::deleteModel($model);

                throw new RuntimeException();
            }

            protected function upsertModel(int|string $id, array $data): Model
            {
                parent::upsertModel($id, $data);

                throw new RuntimeException();
            }
        });

        $this->withoutExceptionHandling();
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('database.connections.secondary', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function defineRoutes($router): void
    {
        parent::defineRoutes($router);

        $router->put('upsert-items/{item}', [ItemController::class, 'upsert']);
    }

    #[Test]
    public function store_creates_item_on_model_connection(): void
    {
        $this->postJson('/items', ['name' => 'New Name'])->assertCreated();

        $this->assertDatabaseHas('items', ['name' => 'New Name'], 'secondary');
        $this->assertDatabaseCount('items', 0);
    }

    #[Test]
    public function store_rolls_back_on_model_connection_when_persisting_fails(): void
    {
        $this->assertThrows(fn () => $this->postJson('/items', ['name' => 'Failing Name']), RuntimeException::class);

        $this->assertDatabaseCount('items', 0, 'secondary');
    }

    #[Test]
    public function update_rolls_back_on_model_connection_when_persisting_fails(): void
    {
        $item = SecondaryConnectionItem::query()->create(['name' => 'Old Name']);

        $this->assertThrows(fn () => $this->putJson("/items/$item->id", ['name' => 'New Name']), RuntimeException::class);

        $this->assertDatabaseHas('items', ['id' => $item->id, 'name' => 'Old Name'], 'secondary');
    }

    #[Test]
    public function destroy_rolls_back_on_model_connection_when_persisting_fails(): void
    {
        $item = SecondaryConnectionItem::query()->create(['name' => 'Old Name']);

        $this->assertThrows(fn () => $this->deleteJson("/items/$item->id"), RuntimeException::class);

        $this->assertDatabaseHas('items', ['id' => $item->id], 'secondary');
    }

    #[Test]
    public function upsert_rolls_back_on_model_connection_when_persisting_fails(): void
    {
        $item = SecondaryConnectionItem::query()->create(['name' => 'Old Name']);

        $this->assertThrows(fn () => $this->putJson("/upsert-items/$item->id", ['name' => 'New Name']), RuntimeException::class);

        $this->assertDatabaseHas('items', ['id' => $item->id, 'name' => 'Old Name'], 'secondary');
    }
}
