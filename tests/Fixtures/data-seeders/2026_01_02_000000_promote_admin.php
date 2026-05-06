<?php

use Bmckay959\DataSeeders\DataSeeder;
use Bmckay959\DataSeeders\Seeder;

return new class extends DataSeeder
{
    public function seed(Seeder $seeder): void
    {
        $seeder->update()
            ->table('users')
            ->where('email', 'admin@example.com')
            ->columns(['name' => 'Site Admin'])
            ->run();
    }
};
