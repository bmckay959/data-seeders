<?php

namespace Bmckay959\DataSeeders\Tests;

use Bmckay959\DataSeeders\DataSeedersServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Bmckay959\\DataSeeders\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        $this->loadPackageMigrations();
        $this->loadFixtureSchema();
    }

    protected function getPackageProviders($app)
    {
        return [
            DataSeedersServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function loadPackageMigrations(): void
    {
        foreach (File::allFiles(__DIR__.'/../database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
        }
    }

    protected function loadFixtureSchema(): void
    {
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->boolean('is_admin')->default(false);
            $table->timestamps();
        });

        Schema::create('products', function ($table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('name');
            $table->integer('price_cents');
        });
    }
}
