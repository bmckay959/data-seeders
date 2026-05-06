<?php

use Bmckay959\DataSeeders\DataSeederRepository;
use Bmckay959\DataSeeders\DataSeederRunner;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->path = __DIR__.'/Fixtures/data-seeders';
    $this->runner = app(DataSeederRunner::class);
    $this->repository = app(DataSeederRepository::class);
});

it('rolls back the latest batch in reverse order', function () {
    $this->runner->run($this->path);

    $rolled = $this->runner->rollback($this->path);

    expect($rolled)->toBe([
        '2026_01_02_000000_promote_admin',
        '2026_01_01_000000_create_initial_users',
    ])
        ->and(DB::table('users')->whereIn('email', ['admin@example.com', 'owner@example.com'])->count())
        ->toBe(0)
        ->and($this->repository->all())->toBe([]);
});

it('returns an empty array when there is nothing to roll back', function () {
    $rolled = $this->runner->rollback($this->path);

    expect($rolled)->toBe([]);
});

it('still removes the row when rollback() is not overridden', function () {
    $tempPath = sys_get_temp_dir().'/data-seeders-rollback-'.uniqid();
    mkdir($tempPath);
    file_put_contents(
        $tempPath.'/2026_01_01_000000_only_seeds.php',
        <<<'PHP'
<?php

use Bmckay959\DataSeeders\DataSeeder;
use Bmckay959\DataSeeders\Seeder;

return new class extends DataSeeder
{
    public function seed(Seeder $seeder): void
    {
        $seeder->add()->table('users')->columns(['name' => 'Solo', 'email' => 'solo@example.com'])->run();
    }
};
PHP
    );

    $this->runner->run($tempPath);
    expect($this->repository->all())->toHaveCount(1);

    $this->runner->rollback($tempPath);

    expect($this->repository->all())->toBe([])
        ->and(DB::table('users')->where('email', 'solo@example.com')->exists())->toBeTrue();

    foreach (glob($tempPath.'/*') as $file) {
        unlink($file);
    }
    rmdir($tempPath);
});
