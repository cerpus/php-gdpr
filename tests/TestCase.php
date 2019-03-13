<?php

namespace Cerpus\Gdpr\Test;

use Faker\Factory;
use Cerpus\Gdpr\GdprServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;
    /** @var Factory */
    protected $faker;

    protected function setUp()
    {
        parent::setUp();
        $this->faker = Factory::create();
        $this->withFactories(__DIR__ . '/../database/factories');
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
    }

    protected function getPackageProviders($app)
    {
        return [
            GdprServiceProvider::class
        ];
    }
}
