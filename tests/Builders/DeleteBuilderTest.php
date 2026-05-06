<?php

use Bmckay959\DataSeeders\Seeder;
use Bmckay959\DataSeeders\Tests\Fixtures\Models\User;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    DB::table('users')->insert([
        ['name' => 'Alice', 'email' => 'alice@example.com'],
        ['name' => 'Bob', 'email' => 'bob@example.com'],
        ['name' => 'Carol', 'email' => 'carol@example.com'],
    ]);
});

function freshSeeder(): Seeder
{
    return new Seeder(app(ConnectionResolverInterface::class));
}

it('deletes rows matching a where clause', function () {
    $deleted = freshSeeder()->delete()
        ->table('users')
        ->where('email', 'bob@example.com')
        ->run();

    expect($deleted)->toBe(1)
        ->and(DB::table('users')->count())->toBe(2)
        ->and(DB::table('users')->where('email', 'bob@example.com')->exists())->toBeFalse();
});

it('deletes via Eloquent model() target', function () {
    $deleted = freshSeeder()->delete()
        ->model(User::class)
        ->where('email', 'alice@example.com')
        ->run();

    expect($deleted)->toBe(1);
});

it('deletes via whereIn()', function () {
    $deleted = freshSeeder()->delete()
        ->table('users')
        ->whereIn('email', ['alice@example.com', 'carol@example.com'])
        ->run();

    expect($deleted)->toBe(2)
        ->and(DB::table('users')->pluck('email')->all())->toBe(['bob@example.com']);
});
