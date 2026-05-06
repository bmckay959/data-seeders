<?php

use Bmckay959\DataSeeders\Exceptions\InvalidSeederOperation;
use Bmckay959\DataSeeders\Seeder;
use Bmckay959\DataSeeders\Tests\Fixtures\Models\User;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Support\Facades\DB;

function makeSeeder(): Seeder
{
    return new Seeder(app(ConnectionResolverInterface::class));
}

it('inserts a single row using table()', function () {
    makeSeeder()->add()
        ->table('users')
        ->columns(['name' => 'Alice', 'email' => 'alice@example.com'])
        ->run();

    expect(DB::table('users')->count())->toBe(1)
        ->and(DB::table('users')->first()->email)->toBe('alice@example.com');
});

it('inserts a list of rows using table()', function () {
    $count = makeSeeder()->add()
        ->table('users')
        ->columns([
            ['name' => 'Alice', 'email' => 'alice@example.com'],
            ['name' => 'Bob', 'email' => 'bob@example.com'],
        ])
        ->run();

    expect($count)->toBe(2)
        ->and(DB::table('users')->pluck('email')->all())
        ->toEqualCanonicalizing(['alice@example.com', 'bob@example.com']);
});

it('inserts using the table associated with an Eloquent model', function () {
    $count = makeSeeder()->add()
        ->model(User::class)
        ->columns(['name' => 'Carol', 'email' => 'carol@example.com'])
        ->run();

    expect($count)->toBe(1)
        ->and(DB::table('users')->where('email', 'carol@example.com')->exists())->toBeTrue();
});

it('accumulates rows across multiple columns() calls', function () {
    $count = makeSeeder()->add()
        ->table('users')
        ->columns(['name' => 'Alice', 'email' => 'alice@example.com'])
        ->columns([
            ['name' => 'Bob', 'email' => 'bob@example.com'],
            ['name' => 'Carol', 'email' => 'carol@example.com'],
        ])
        ->run();

    expect($count)->toBe(3);
});

it('returns 0 when no rows are added', function () {
    $count = makeSeeder()->add()->table('users')->run();

    expect($count)->toBe(0)
        ->and(DB::table('users')->count())->toBe(0);
});

it('throws when no table or model is set', function () {
    makeSeeder()->add()
        ->columns(['name' => 'Alice', 'email' => 'alice@example.com'])
        ->run();
})->throws(InvalidSeederOperation::class);

it('throws when given a non-Model class via model()', function () {
    makeSeeder()->add()->model(stdClass::class);
})->throws(InvalidSeederOperation::class);

it('skips duplicates with insertOrIgnore()', function () {
    DB::table('users')->insert(['name' => 'Alice', 'email' => 'alice@example.com']);

    $inserted = makeSeeder()->add()
        ->table('users')
        ->columns([
            ['name' => 'Alice', 'email' => 'alice@example.com'],
            ['name' => 'Bob', 'email' => 'bob@example.com'],
        ])
        ->insertOrIgnore();

    expect($inserted)->toBe(1)
        ->and(DB::table('users')->count())->toBe(2)
        ->and(DB::table('users')->where('email', 'bob@example.com')->exists())->toBeTrue();
});

it('returns 0 from insertOrIgnore() when no rows are added', function () {
    expect(makeSeeder()->add()->table('users')->insertOrIgnore())->toBe(0);
});

it('inserts new rows and updates existing rows with upsert()', function () {
    DB::table('users')->insert([
        'name' => 'Alice',
        'email' => 'alice@example.com',
        'is_admin' => false,
    ]);

    makeSeeder()->add()
        ->table('users')
        ->columns([
            ['name' => 'Alice Smith', 'email' => 'alice@example.com', 'is_admin' => true],
            ['name' => 'Bob', 'email' => 'bob@example.com', 'is_admin' => false],
        ])
        ->upsert(['email']);

    $alice = DB::table('users')->where('email', 'alice@example.com')->first();
    $bob = DB::table('users')->where('email', 'bob@example.com')->first();

    expect(DB::table('users')->count())->toBe(2)
        ->and($alice->name)->toBe('Alice Smith')
        ->and((bool) $alice->is_admin)->toBeTrue()
        ->and($bob->name)->toBe('Bob');
});

it('only updates the explicitly given columns on upsert()', function () {
    DB::table('users')->insert([
        'name' => 'Alice',
        'email' => 'alice@example.com',
        'is_admin' => true,
    ]);

    makeSeeder()->add()
        ->table('users')
        ->columns([
            ['name' => 'Alice Smith', 'email' => 'alice@example.com', 'is_admin' => false],
        ])
        ->upsert(['email'], ['name']);

    $alice = DB::table('users')->where('email', 'alice@example.com')->first();

    expect($alice->name)->toBe('Alice Smith')
        ->and((bool) $alice->is_admin)->toBeTrue();
});

it('returns 0 from upsert() when no rows are added', function () {
    expect(makeSeeder()->add()->table('users')->upsert(['email']))->toBe(0);
});

it('throws when upsert() is given an empty uniqueBy', function () {
    makeSeeder()->add()
        ->table('users')
        ->columns(['name' => 'Alice', 'email' => 'alice@example.com'])
        ->upsert([]);
})->throws(InvalidSeederOperation::class);
