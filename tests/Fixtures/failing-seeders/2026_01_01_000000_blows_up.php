<?php

use Bmckay959\DataSeeders\DataSeeder;
use Bmckay959\DataSeeders\Seeder;

return new class extends DataSeeder
{
    public function seed(Seeder $seeder): void
    {
        $seeder->add()
            ->table('users')
            ->columns(['name' => 'Ghost', 'email' => 'ghost@example.com'])
            ->run();

        throw new RuntimeException('boom');
    }
};
