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
