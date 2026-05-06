<?php

namespace Bmckay959\DataSeeders\Commands;

use Bmckay959\DataSeeders\DataSeederRunner;
use Bmckay959\DataSeeders\Exceptions\DataSeederFileException;
use Illuminate\Console\Command;

class DataSeedersRollbackCommand extends Command
{
    public $signature = 'data-seeders:rollback
        {--path= : Override the seeder path}
        {--batch= : Roll back a specific batch number}';

    public $description = 'Roll back the latest batch of data seeders';

    public function handle(DataSeederRunner $runner): int
    {
        $path = $this->option('path') ?: config('data-seeders.path');
        $batch = $this->option('batch');
        $batch = $batch === null ? null : (int) $batch;

        $runner->setOutput(fn (string $level, string $message) => $this->{$level}($message));

        try {
            $rolled = $runner->rollback($path, $batch);
        } catch (DataSeederFileException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($rolled === []) {
            return self::SUCCESS;
        }

        $this->info('Rolled back '.count($rolled).' seeder(s).');

        return self::SUCCESS;
    }
}
