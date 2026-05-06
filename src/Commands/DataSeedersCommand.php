<?php

namespace Bmckay959\DataSeeders\Commands;

use Illuminate\Console\Command;

class DataSeedersCommand extends Command
{
    public $signature = 'data-seeders';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
