<?php

use Bmckay959\DataSeeders\DataSeederRunner;
use Bmckay959\DataSeeders\Seeder;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Support\Facades\DB;

function rawSeeder(): Seeder
{
    return new Seeder(app(ConnectionResolverInterface::class));
}

it('runs a raw SQL statement with bindings', function () {
    DB::table('users')->insert([
        ['name' => 'Alice', 'email' => 'alice@example.com'],
        ['name' => 'Bob', 'email' => 'bob@example.com'],
    ]);

    $result = rawSeeder()->raw(
        'UPDATE users SET name = ? WHERE email = ?',
        ['Alice Smith', 'alice@example.com'],
    );

    expect($result)->toBeTrue()
        ->and(DB::table('users')->where('email', 'alice@example.com')->value('name'))
        ->toBe('Alice Smith');
});

it('exposes the underlying connection to a callback', function () {
    DB::table('users')->insert(['name' => 'Alice', 'email' => 'alice@example.com']);

    $returned = rawSeeder()->raw(function ($db) {
        expect($db)->toBeInstanceOf(Connection::class);

        $db->table('users')->where('email', 'alice@example.com')->update(['name' => 'Alice Smith']);

        return $db->table('users')->count();
    });

    expect($returned)->toBe(1)
        ->and(DB::table('users')->where('email', 'alice@example.com')->value('name'))
        ->toBe('Alice Smith');
});

it('shares the seeder transaction so callback errors roll back inserts', function () {
    $runner = app(DataSeederRunner::class);
    $tempPath = sys_get_temp_dir().'/data-seeders-raw-'.uniqid();
    mkdir($tempPath);

    file_put_contents(
        $tempPath.'/2026_01_01_000000_raw_failure.php',
        <<<'PHP'
<?php

use Bmckay959\DataSeeders\DataSeeder;
use Bmckay959\DataSeeders\Seeder;

return new class extends DataSeeder
{
    public function seed(Seeder $seeder): void
    {
        $seeder->add()->table('users')->columns(['name' => 'Ghost', 'email' => 'ghost@example.com'])->run();

        $seeder->raw(function () {
            throw new \RuntimeException('boom');
        });
    }
};
PHP
    );

    expect(fn () => $runner->run($tempPath))->toThrow(RuntimeException::class);
    expect(DB::table('users')->where('email', 'ghost@example.com')->exists())->toBeFalse();

    foreach (glob($tempPath.'/*') as $file) {
        unlink($file);
    }
    rmdir($tempPath);
});
