<?php

namespace Bmckay959\DataSeeders\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class MakeDataSeederCommand extends Command
{
    public $signature = 'make:data-seeder
        {name : The name of the data seeder, e.g. CreateInitialAdmins}
        {--path= : Override the seeder path}';

    public $description = 'Create a new data seeder file';

    public function handle(Filesystem $files): int
    {
        $name = (string) $this->argument('name');
        $snake = Str::snake(class_basename($name));
        $path = $this->option('path') ?: config('data-seeders.path');

        if (! $files->isDirectory($path)) {
            $files->makeDirectory($path, 0755, true);
        }

        $timestamp = Carbon::now()->format('Y_m_d_His');
        $filename = "{$timestamp}_{$snake}.php";
        $target = rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$filename;

        if ($files->exists($target)) {
            $this->error("Data seeder already exists: {$target}");

            return self::FAILURE;
        }

        $stub = $this->resolveStub($files);
        $files->put($target, $stub);

        $this->info("Created data seeder: {$filename}");

        return self::SUCCESS;
    }

    protected function resolveStub(Filesystem $files): string
    {
        $published = base_path('stubs/data-seeder.stub');

        if ($files->exists($published)) {
            return $files->get($published);
        }

        return $files->get(__DIR__.'/../../stubs/data-seeder.stub');
    }
}
