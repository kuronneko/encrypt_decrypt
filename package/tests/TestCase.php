<?php

namespace Kuronneko\LaravelDevExtremeEncrypted\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Kuronneko\LaravelDevExtremeEncrypted\DevExtremeEncryptedServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            DevExtremeEncryptedServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Setup encryption key
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
    }
}
