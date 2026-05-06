<?php

use Bmckay959\DataSeeders\DataSeederRepository;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->path = __DIR__.'/../Fixtures/data-seeders';
    config()->set('data-seeders.path', $this->path);
});

it('runs pending seeders via the artisan command', function () {
    $this->artisan('data-seeders')
        ->expectsOutputToContain('Seeded:  2026_01_01_000000_create_initial_users')
        ->assertSuccessful();

    expect(DB::table('users')->count())->toBe(2);
    expect(app(DataSeederRepository::class)->all())->toHaveCount(2);
});

it('rolls back via the rollback command', function () {
    $this->artisan('data-seeders')->assertSuccessful();
    $this->artisan('data-seeders:rollback')->assertSuccessful();

    expect(DB::table('users')->whereIn('email', ['admin@example.com', 'owner@example.com'])->count())->toBe(0)
        ->and(app(DataSeederRepository::class)->all())->toBe([]);
});

it('reports status of every discovered seeder', function () {
    $this->artisan('data-seeders:status')
        ->expectsOutputToContain('2026_01_01_000000_create_initial_users')
        ->expectsOutputToContain('Pending')
        ->assertSuccessful();

    $this->artisan('data-seeders')->assertSuccessful();

    $this->artisan('data-seeders:status')
        ->expectsOutputToContain('Ran')
        ->assertSuccessful();
});
