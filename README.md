# Data Seeders

[![Latest Version on Packagist](https://img.shields.io/packagist/v/bmckay959/data-seeders.svg?style=flat-square)](https://packagist.org/packages/bmckay959/data-seeders)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/bmckay959/data-seeders/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/bmckay959/data-seeders/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/bmckay959/data-seeders.svg?style=flat-square)](https://packagist.org/packages/bmckay959/data-seeders)

Seed real data into your database with the same workflow as Laravel migrations. Each seeder file is run **once**, recorded in a `data_seeders` state table, and grouped into a batch so you can roll the latest set back if you need to.

```php
use Bmckay959\DataSeeders\DataSeeder;
use Bmckay959\DataSeeders\Seeder;

return new class extends DataSeeder
{
    public function seed(Seeder $seeder): void
    {
        $seeder->add()
            ->table('users')
            ->columns([
                ['name' => 'Admin', 'email' => 'admin@example.com', 'is_admin' => true],
                ['name' => 'Owner', 'email' => 'owner@example.com', 'is_admin' => true],
            ])
            ->run();
    }

    public function rollback(Seeder $seeder): void
    {
        $seeder->delete()
            ->table('users')
            ->whereIn('email', ['admin@example.com', 'owner@example.com'])
            ->run();
    }
};
```

## Installation

```bash
composer require bmckay959/data-seeders
```

Publish and run the migration that creates the `data_seeders` state table:

```bash
php artisan vendor:publish --tag="data-seeders-migrations"
php artisan migrate
```

Optionally publish the config file:

```bash
php artisan vendor:publish --tag="data-seeders-config"
```

```php
return [
    'path' => database_path('data-seeders'),
    'table' => 'data_seeders',
    'connection' => null,
];
```

## Usage

### Creating a seeder

```bash
php artisan make:data-seeder CreateInitialAdmins
```

This generates a timestamp-prefixed file under `database/data-seeders/`, for example `2026_05_06_120000_create_initial_admins.php`. Each seeder is an anonymous class that extends `Bmckay959\DataSeeders\DataSeeder` and must implement `seed(Seeder $seeder)`. Implementing `rollback(Seeder $seeder)` is optional.

### Running pending seeders

```bash
php artisan data-seeders
```

The runner discovers files in the configured path, runs every seeder that has not yet been recorded, and writes a row to the `data_seeders` table. All discovered seeders run in the same batch. Each seeder runs inside a database transaction; if `seed()` throws, no row is written to the state table and the partial changes are rolled back.

### Inspecting status

```bash
php artisan data-seeders:status
```

Shows a table of each discovered seeder with its batch and run timestamp, or `Pending` if it has not run yet.

### Rolling back

```bash
php artisan data-seeders:rollback
php artisan data-seeders:rollback --batch=2
```

The rollback command reverses the most recent batch (or the explicit batch passed via `--batch`). Each seeder's `rollback()` method is invoked inside a transaction, then its row is removed from the state table. Seeders that don't override `rollback()` are still un-recorded — you'll need to clean any data they wrote in a follow-up seeder.

## The fluent API

Every seeder receives a `Bmckay959\DataSeeders\Seeder` that exposes three builders.

### `add()` — strict insert

```php
$seeder->add()
    ->table('users')                 // OR ->model(\App\Models\User::class)
    ->columns([
        ['name' => 'Alice', 'email' => 'alice@example.com'],
        ['name' => 'Bob',   'email' => 'bob@example.com'],
    ])
    ->run();
```

`columns()` accepts a single associative row or a list of rows, and may be called multiple times to accumulate rows before the terminal call. `run()` performs a strict insert — it does not upsert or skip duplicates.

If you need to be tolerant of existing rows, use one of the alternative terminals instead of `run()`:

```php
$seeder->add()
    ->table('users')
    ->columns([
        ['name' => 'Alice', 'email' => 'alice@example.com'],
        ['name' => 'Bob',   'email' => 'bob@example.com'],
    ])
    ->insertOrIgnore();          // skip rows that conflict on unique/primary keys

$seeder->add()
    ->table('users')
    ->columns([
        ['name' => 'Alice Smith', 'email' => 'alice@example.com'],
        ['name' => 'Bob',         'email' => 'bob@example.com'],
    ])
    ->upsert(['email']);         // insert new rows, update matching rows on conflict

$seeder->add()
    ->table('users')
    ->columns([
        ['name' => 'Alice Smith', 'email' => 'alice@example.com', 'is_admin' => false],
    ])
    ->upsert(['email'], ['name']); // only `name` is updated on conflict; other columns are preserved
```

`upsert()` accepts the unique columns used to detect conflicts as the first argument (a single column name or array). The optional second argument lists which columns to overwrite on conflict; when omitted, every column except the unique-by columns is updated.

### `update()`

```php
$seeder->update()
    ->table('users')
    ->where('email', 'admin@example.com')
    ->columns(['name' => 'Site Admin'])
    ->run();
```

`where()` accepts either `(column, value)` or `(column, operator, value)`, and `whereIn()` is also supported.

### `delete()`

```php
$seeder->delete()
    ->model(\App\Models\User::class)
    ->whereIn('email', ['admin@example.com', 'owner@example.com'])
    ->run();
```

### `raw()` — escape hatch

When the fluent builders don't cover what you need, drop down to `raw()`. It accepts either a SQL string with bindings, or a closure that receives the underlying `Illuminate\Database\Connection`:

```php
$seeder->raw(
    'UPDATE users SET name = ? WHERE email = ?',
    ['Alice Smith', 'alice@example.com'],
);

$seeder->raw(function ($db) {
    $db->statement('CREATE INDEX users_email_idx ON users (email)');

    $db->table('users')
        ->where('created_at', '<', now()->subYear())
        ->update(['archived' => true]);
});
```

The closure runs inside the same database transaction the runner has already opened around `seed()` / `rollback()`, so a thrown exception rolls back everything in the seeder. The string form returns the underlying `statement()` result (a `bool`); the closure form returns whatever the closure returns.

### Loading rows from JSON

Because `columns()` just accepts an array, loading a JSON file is trivial:

```php
public function seed(Seeder $seeder): void
{
    $rows = json_decode(file_get_contents(__DIR__.'/data/products.json'), true);

    $seeder->add()
        ->model(\App\Models\Product::class)
        ->columns($rows)
        ->run();
}
```

The same approach works for CSVs, YAML, or any other source — the seeder is just a regular PHP class.

### Switching connection

```php
$seeder->connection('reporting')
    ->add()
    ->table('events')
    ->columns([...])
    ->run();
```

`model()` automatically adopts the model's connection when one isn't already set.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Ben McKay](https://github.com/bmckay959)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
