<?php

namespace Bmckay959\DataSeeders;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Bmckay959\DataSeeders\Commands\DataSeedersCommand;

class DataSeedersServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('data-seeders')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_data_seeders_table')
            ->hasCommand(DataSeedersCommand::class);
    }
}
