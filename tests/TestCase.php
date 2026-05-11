<?php

namespace SgFlores\Cruder\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use SgFlores\Cruder\CruderServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up database
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        // Run migrations
        $this->artisan('migrate', ['--database' => 'testing']);
    }

    protected function tearDown(): void
    {
        // Clean up any active transactions
        if ($this->app['db']->transactionLevel() > 0) {
            $this->app['db']->rollBack();
        }

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [
            CruderServiceProvider::class,
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
