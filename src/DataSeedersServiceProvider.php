<?php

namespace Bmckay959\DataSeeders;

use Bmckay959\DataSeeders\Commands\DataSeedersCommand;
use Bmckay959\DataSeeders\Commands\DataSeedersRollbackCommand;
use Bmckay959\DataSeeders\Commands\DataSeedersStatusCommand;
use Bmckay959\DataSeeders\Commands\MakeDataSeederCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class DataSeedersServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('data-seeders')
            ->hasConfigFile()
            ->hasMigration('create_data_seeders_table')
            ->hasCommands([
                DataSeedersCommand::class,
                DataSeedersRollbackCommand::class,
                DataSeedersStatusCommand::class,
                MakeDataSeederCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(DataSeederRepository::class, function ($app) {
            return new DataSeederRepository(
                $app['db'],
                config('data-seeders.table', 'data_seeders'),
                config('data-seeders.connection'),
            );
        });

        $this->app->singleton(DataSeederRunner::class, function ($app) {
            return new DataSeederRunner(
                $app['files'],
                $app->make(DataSeederRepository::class),
                $app['db'],
                config('data-seeders.connection'),
            );
        });
    }

    public function packageBooted(): void
    {
        $this->publishes([
            __DIR__.'/../stubs/data-seeder.stub' => base_path('stubs/data-seeder.stub'),
        ], 'data-seeders-stubs');
    }
}
