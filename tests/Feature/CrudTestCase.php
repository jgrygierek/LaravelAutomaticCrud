<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\ItemController;
use JG\LaravelAutomaticCrud\Tests\TestCase;

abstract class CrudTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('items', static function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('secret')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('items');

        parent::tearDown();
    }

    protected function defineRoutes($router): void
    {
        $router->apiResource('items', ItemController::class);
    }
}
