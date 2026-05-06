<?php

namespace Bmckay959\DataSeeders;

use Bmckay959\DataSeeders\Exceptions\DataSeederFileException;
use Closure;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Filesystem\Filesystem;
use Throwable;

class DataSeederRunner
{
    /**
     * Optional callback used to report progress to the console.
     *
     * @var Closure(string, string): void|null
     */
    protected ?Closure $output = null;

    public function __construct(
        protected Filesystem $files,
        protected DataSeederRepository $repository,
        protected ConnectionResolverInterface $resolver,
        protected ?string $connection = null,
    ) {}

    /**
     * Provide a closure that receives status updates: ($level, $message).
     *
     * @param  Closure(string, string): void  $callback
     */
    public function setOutput(Closure $callback): void
    {
        $this->output = $callback;
    }

    /**
     * Run all pending seeders in the given path. Returns the names of the
     * seeders that ran. The empty list signals "nothing to do".
     *
     * @return array<int, string>
     */
    public function run(string $path): array
    {
        $this->ensureRepositoryExists();

        $files = $this->discover($path);
        $ran = $this->repository->getRan();
        $pending = array_values(array_diff($files, $ran));

        if ($pending === []) {
            $this->report('info', 'Nothing to seed.');

            return [];
        }

        $batch = $this->repository->getNextBatchNumber();

        foreach ($pending as $name) {
            $this->runSeed($path, $name, $batch);
        }

        return $pending;
    }

    /**
     * Roll back the most recent batch. Returns the names of the seeders that
     * were rolled back.
     *
     * @return array<int, string>
     */
    public function rollback(string $path, ?int $batch = null): array
    {
        $this->ensureRepositoryExists();

        $names = $batch === null
            ? $this->repository->getLast()
            : $this->repository->getByBatch($batch);

        if ($names === []) {
            $this->report('info', 'Nothing to roll back.');

            return [];
        }

        $rolled = [];

        foreach ($names as $name) {
            $this->rollbackSeed($path, $name);
            $rolled[] = $name;
        }

        return $rolled;
    }

    /**
     * Determine the seeders found on disk and pair them with their state.
     *
     * @return array<int, array{name: string, batch: int|null, ran_at: string|null}>
     */
    public function status(string $path): array
    {
        $this->ensureRepositoryExists();

        $files = $this->discover($path);
        $records = collect($this->repository->all())->keyBy('seeder');

        return array_map(function (string $name) use ($records) {
            $record = $records->get($name);

            return [
                'name' => $name,
                'batch' => $record['batch'] ?? null,
                'ran_at' => $record['ran_at'] ?? null,
            ];
        }, $files);
    }

    /**
     * Discover seeder files in the given directory and return their names
     * (basename, no extension), sorted lexicographically.
     *
     * @return array<int, string>
     */
    public function discover(string $path): array
    {
        if (! $this->files->isDirectory($path)) {
            return [];
        }

        $found = $this->files->glob($path.DIRECTORY_SEPARATOR.'*_*.php');

        $names = array_map(
            fn (string $file) => $this->files->name($file),
            $found ?: [],
        );

        sort($names);

        return $names;
    }

    /**
     * Resolve a seeder file into a DataSeeder instance.
     */
    public function resolve(string $path, string $name): DataSeeder
    {
        $file = $path.DIRECTORY_SEPARATOR.$name.'.php';

        if (! $this->files->exists($file)) {
            throw DataSeederFileException::notFound($name, $file);
        }

        $instance = require $file;

        if (! $instance instanceof DataSeeder) {
            throw DataSeederFileException::invalidReturn($name);
        }

        return $instance;
    }

    protected function runSeed(string $path, string $name, int $batch): void
    {
        $instance = $this->resolve($path, $name);
        $seeder = new Seeder($this->resolver, $this->connection);
        $connection = $this->resolver->connection($this->connection);

        $this->report('info', "Seeding: {$name}");

        try {
            $connection->transaction(function () use ($instance, $seeder) {
                $instance->seed($seeder);
            });
        } catch (Throwable $e) {
            $this->report('error', "Failed: {$name}");
            throw $e;
        }

        $this->repository->log($name, $batch);
        $this->report('info', "Seeded:  {$name}");
    }

    protected function rollbackSeed(string $path, string $name): void
    {
        $instance = $this->resolve($path, $name);
        $seeder = new Seeder($this->resolver, $this->connection);
        $connection = $this->resolver->connection($this->connection);

        $this->report('info', "Rolling back: {$name}");

        try {
            $connection->transaction(function () use ($instance, $seeder) {
                $instance->rollback($seeder);
            });
        } catch (Throwable $e) {
            $this->report('error', "Failed rollback: {$name}");
            throw $e;
        }

        $this->repository->delete($name);
        $this->report('info', "Rolled back: {$name}");
    }

    protected function ensureRepositoryExists(): void
    {
        if ($this->repository->repositoryExists()) {
            return;
        }

        throw DataSeederFileException::stateTableMissing();
    }

    protected function report(string $level, string $message): void
    {
        if ($this->output !== null) {
            ($this->output)($level, $message);
        }
    }
}
