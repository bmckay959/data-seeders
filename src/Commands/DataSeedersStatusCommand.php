<?php

namespace Bmckay959\DataSeeders\Commands;

use Bmckay959\DataSeeders\DataSeederRunner;
use Bmckay959\DataSeeders\Exceptions\DataSeederFileException;
use Illuminate\Console\Command;

class DataSeedersStatusCommand extends Command
{
    public $signature = 'data-seeders:status {--path= : Override the seeder path}';

    public $description = 'Show the status of every data seeder';

    public function handle(DataSeederRunner $runner): int
    {
        $path = $this->option('path') ?: config('data-seeders.path');

        try {
            $rows = $runner->status($path);
        } catch (DataSeederFileException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($rows === []) {
            $this->info('No data seeders found at '.$path);

            return self::SUCCESS;
        }

        $this->table(
            ['Seeder', 'Batch', 'Ran at', 'Status'],
            array_map(fn (array $row) => [
                $row['name'],
                $row['batch'] ?? '-',
                $row['ran_at'] ?? '-',
                $row['batch'] === null ? 'Pending' : 'Ran',
            ], $rows),
        );

        return self::SUCCESS;
    }
}
