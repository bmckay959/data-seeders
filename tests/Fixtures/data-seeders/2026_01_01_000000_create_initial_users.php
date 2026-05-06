<?php

use Bmckay959\DataSeeders\DataSeeder;
use Bmckay959\DataSeeders\Seeder;

return new class extends DataSeeder
{
    public function seed(Seeder $seeder): void
    {
        $seeder->add()
            ->table('users')
            ->columns([
                ['name' => 'Admin', 'email' => 'admin@example.com', 'is_admin' => true],
                ['name' => 'Owner', 'email' => 'owner@example.com', 'is_admin' => true],
            ])
            ->run();
    }

    public function rollback(Seeder $seeder): void
    {
        $seeder->delete()
            ->table('users')
            ->whereIn('email', ['admin@example.com', 'owner@example.com'])
            ->run();
    }
};
