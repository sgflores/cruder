<?php

namespace SgFlores\Cruder\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class UnitTestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            \SgFlores\Cruder\CruderServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        
        // Setup cache
        $app['config']->set('cache.default', 'array');
        $app['config']->set('cache.stores.array', [
            'driver' => 'array',
        ]);
        
        // Setup session
        $app['config']->set('session.driver', 'array');
        
        // Setup queue
        $app['config']->set('queue.default', 'sync');
        
        // Setup app
        $app['config']->set('app.env', 'testing');
        $app['config']->set('app.debug', true);
    }
}
