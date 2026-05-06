<?php

use Bmckay959\DataSeeders\DataSeederRepository;
use Bmckay959\DataSeeders\DataSeederRunner;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->path = __DIR__.'/Fixtures/data-seeders';
    $this->failingPath = __DIR__.'/Fixtures/failing-seeders';
    $this->runner = app(DataSeederRunner::class);
    $this->repository = app(DataSeederRepository::class);
});

it('runs all pending seeders and records them in a single batch', function () {
    $ran = $this->runner->run($this->path);

    expect($ran)->toBe([
        '2026_01_01_000000_create_initial_users',
        '2026_01_02_000000_promote_admin',
    ])
        ->and(DB::table('users')->where('name', 'Site Admin')->exists())->toBeTrue()
        ->and(DB::table('users')->where('email', 'owner@example.com')->exists())->toBeTrue();

    $rows = $this->repository->all();
    expect($rows)->toHaveCount(2)
        ->and($rows[0]['batch'])->toBe(1)
        ->and($rows[1]['batch'])->toBe(1);
});

it('does nothing on a second run', function () {
    $this->runner->run($this->path);
    $second = $this->runner->run($this->path);

    expect($second)->toBe([])
        ->and(DB::table('users')->count())->toBe(2);
});

it('increments the batch on subsequent runs that find new seeders', function () {
    $this->runner->run($this->path);
    expect($this->repository->getLastBatchNumber())->toBe(1);

    $extra = sys_get_temp_dir().'/data-seeders-test-'.uniqid();
    mkdir($extra);
    foreach (glob($this->path.'/*.php') as $file) {
        copy($file, $extra.'/'.basename($file));
    }
    file_put_contents(
        $extra.'/2026_01_03_000000_extra.php',
        <<<'PHP'
<?php

use Bmckay959\DataSeeders\DataSeeder;
use Bmckay959\DataSeeders\Seeder;

return new class extends DataSeeder
{
    public function seed(Seeder $seeder): void
    {
        $seeder->add()->table('users')->columns(['name' => 'Extra', 'email' => 'extra@example.com'])->run();
    }
};
PHP
    );

    $ran = $this->runner->run($extra);

    expect($ran)->toBe(['2026_01_03_000000_extra'])
        ->and($this->repository->getLastBatchNumber())->toBe(2);

    foreach (glob($extra.'/*') as $file) {
        unlink($file);
    }
    rmdir($extra);
});

it('rolls back transactional changes when a seed throws', function () {
    expect(fn () => $this->runner->run($this->failingPath))->toThrow(RuntimeException::class);

    expect(DB::table('users')->where('email', 'ghost@example.com')->exists())->toBeFalse()
        ->and($this->repository->all())->toBe([]);
});

it('returns sorted seeder names from discover()', function () {
    $names = $this->runner->discover($this->path);

    expect($names)->toBe([
        '2026_01_01_000000_create_initial_users',
        '2026_01_02_000000_promote_admin',
    ]);
});
