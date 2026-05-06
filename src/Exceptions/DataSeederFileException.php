<?php

namespace Bmckay959\DataSeeders\Exceptions;

use RuntimeException;

class DataSeederFileException extends RuntimeException
{
    public static function notFound(string $name, string $path): self
    {
        return new self("Could not find data seeder [{$name}] at {$path}.");
    }

    public static function invalidReturn(string $name): self
    {
        return new self(
            "Data seeder [{$name}] must return an instance of ".
            'Bmckay959\\DataSeeders\\DataSeeder.'
        );
    }

    public static function stateTableMissing(): self
    {
        return new self(
            'The data seeders state table has not been created. '.
            'Run `php artisan vendor:publish --tag="data-seeders-migrations"` and '.
            '`php artisan migrate` before running data seeders.'
        );
    }
}
