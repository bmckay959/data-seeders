<?php

namespace Bmckay959\DataSeeders;

abstract class DataSeeder
{
    /**
     * Run the seeder. Implementations should describe the desired data state
     * using the fluent helpers exposed on the provided Seeder instance.
     */
    abstract public function seed(Seeder $seeder): void;

    /**
     * Reverse the seeder. Override this method if the seeder should be
     * rollback-aware via `php artisan data-seeders:rollback`.
     */
    public function rollback(Seeder $seeder): void
    {
        // No-op by default. Override to support rollback.
    }
}
