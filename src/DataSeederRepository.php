<?php

namespace Bmckay959\DataSeeders;

use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;

class DataSeederRepository
{
    public function __construct(
        protected ConnectionResolverInterface $resolver,
        protected string $table = 'data_seeders',
        protected ?string $connection = null,
    ) {}

    /**
     * Determine if the state table exists on the configured connection.
     */
    public function repositoryExists(): bool
    {
        $connection = $this->connection();

        return $connection->getSchemaBuilder()->hasTable($this->table);
    }

    /**
     * Get the names of all seeders that have already run, ordered by batch
     * then by name.
     *
     * @return array<int, string>
     */
    public function getRan(): array
    {
        return $this->query()
            ->orderBy('batch')
            ->orderBy('seeder')
            ->pluck('seeder')
            ->all();
    }

    /**
     * Get the seeders that ran in the most recent batch.
     *
     * @return array<int, string>
     */
    public function getLast(): array
    {
        $batch = $this->getLastBatchNumber();

        if ($batch === 0) {
            return [];
        }

        return $this->getByBatch($batch);
    }

    /**
     * Get all seeders that ran in the given batch, ordered most recent first.
     *
     * @return array<int, string>
     */
    public function getByBatch(int $batch): array
    {
        return $this->query()
            ->where('batch', $batch)
            ->orderByDesc('seeder')
            ->pluck('seeder')
            ->all();
    }

    /**
     * Return all rows in the state table.
     *
     * @return array<int, array{seeder: string, batch: int, ran_at: string}>
     */
    public function all(): array
    {
        return $this->query()
            ->orderBy('batch')
            ->orderBy('seeder')
            ->get(['seeder', 'batch', 'ran_at'])
            ->map(fn ($row) => [
                'seeder' => $row->seeder,
                'batch' => (int) $row->batch,
                'ran_at' => (string) $row->ran_at,
            ])
            ->all();
    }

    /**
     * Record that a seeder has run as part of the given batch.
     */
    public function log(string $seeder, int $batch): void
    {
        $this->query()->insert([
            'seeder' => $seeder,
            'batch' => $batch,
            'ran_at' => Carbon::now(),
        ]);
    }

    /**
     * Remove the record for the given seeder.
     */
    public function delete(string $seeder): void
    {
        $this->query()->where('seeder', $seeder)->delete();
    }

    /**
     * Get the next batch number.
     */
    public function getNextBatchNumber(): int
    {
        return $this->getLastBatchNumber() + 1;
    }

    /**
     * Get the most recent batch number recorded.
     */
    public function getLastBatchNumber(): int
    {
        return (int) ($this->query()->max('batch') ?? 0);
    }

    protected function query(): QueryBuilder
    {
        return $this->connection()->table($this->table);
    }

    protected function connection(): Connection
    {
        $connection = $this->resolver->connection($this->connection);

        if (! $connection instanceof Connection) {
            throw new \RuntimeException('Expected an Illuminate\\Database\\Connection instance.');
        }

        return $connection;
    }
}
