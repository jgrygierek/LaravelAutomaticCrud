<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests;

use JG\LaravelAutomaticCrud\AutomaticCrudServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [AutomaticCrudServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('automatic-crud.configs.default.namespaces.model', 'JG\LaravelAutomaticCrud\Tests\Support\Models');
        $app['config']->set('automatic-crud.configs.default.namespaces.resource', 'JG\LaravelAutomaticCrud\Tests\Support\Resources');
        $app['config']->set('automatic-crud.configs.default.namespaces.request', 'JG\LaravelAutomaticCrud\Tests\Support\Requests');
        $app['config']->set('automatic-crud.configs.default.namespaces.event', 'JG\LaravelAutomaticCrud\Tests\Support\Events');
    }
}
