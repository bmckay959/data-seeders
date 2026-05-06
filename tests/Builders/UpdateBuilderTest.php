<?php

use Bmckay959\DataSeeders\Exceptions\InvalidSeederOperation;
use Bmckay959\DataSeeders\Seeder;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    DB::table('users')->insert([
        ['name' => 'Alice', 'email' => 'alice@example.com', 'is_admin' => false],
        ['name' => 'Bob', 'email' => 'bob@example.com', 'is_admin' => false],
        ['name' => 'Carol', 'email' => 'carol@example.com', 'is_admin' => false],
    ]);
});

function newSeeder(): Seeder
{
    return new Seeder(app(ConnectionResolverInterface::class));
}

it('updates rows matching a where clause', function () {
    $affected = newSeeder()->update()
        ->table('users')
        ->where('email', 'alice@example.com')
        ->columns(['name' => 'Alice Smith'])
        ->run();

    expect($affected)->toBe(1)
        ->and(DB::table('users')->where('email', 'alice@example.com')->value('name'))->toBe('Alice Smith');
});

it('supports a where with operator', function () {
    $affected = newSeeder()->update()
        ->table('users')
        ->where('id', '>=', 2)
        ->columns(['is_admin' => true])
        ->run();

    expect($affected)->toBe(2)
        ->and(DB::table('users')->where('is_admin', true)->count())->toBe(2);
});

it('supports whereIn', function () {
    $affected = newSeeder()->update()
        ->table('users')
        ->whereIn('email', ['alice@example.com', 'bob@example.com'])
        ->columns(['is_admin' => true])
        ->run();

    expect($affected)->toBe(2);
});

it('throws when columns() was never called', function () {
    newSeeder()->update()
        ->table('users')
        ->where('id', 1)
        ->run();
})->throws(InvalidSeederOperation::class);
