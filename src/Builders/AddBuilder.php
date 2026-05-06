<?php

namespace Bmckay959\DataSeeders\Builders;

use Bmckay959\DataSeeders\Exceptions\InvalidSeederOperation;

class AddBuilder extends Builder
{
    /**
     * @var array<int, array<string, mixed>>
     */
    protected array $rows = [];

    /**
     * Add one or many rows. Pass an associative array for a single row, or
     * an array of associative arrays for multiple rows. May be called more
     * than once to accumulate rows before `run()` is invoked.
     *
     * @param  array<string, mixed>|array<int, array<string, mixed>>  $columns
     */
    public function columns(array $columns): self
    {
        if ($columns === []) {
            return $this;
        }

        if (array_is_list($columns)) {
            foreach ($columns as $row) {
                if (! is_array($row)) {
                    throw InvalidSeederOperation::invalidRow();
                }

                $this->rows[] = $row;
            }

            return $this;
        }

        $this->rows[] = $columns;

        return $this;
    }

    /**
     * Insert all accumulated rows. Returns the number of rows inserted.
     */
    public function run(): int
    {
        if ($this->rows === []) {
            return 0;
        }

        $this->query()->insert($this->rows);

        return count($this->rows);
    }

    /**
     * Insert all accumulated rows, silently ignoring duplicates as defined by
     * the underlying database (typically primary or unique key conflicts).
     * Returns the number of rows actually inserted.
     */
    public function insertOrIgnore(): int
    {
        if ($this->rows === []) {
            return 0;
        }

        return $this->query()->insertOrIgnore($this->rows);
    }

    /**
     * Insert rows or update them when they conflict on the given unique
     * columns.
     *
     * @param  array<int, string>|string  $uniqueBy  The unique column(s) used to detect existing rows.
     * @param  array<int, string>|null  $update  Columns to update on conflict. When null, all
     *                                           columns except $uniqueBy are updated.
     * @return int Number of rows inserted plus rows updated (driver-dependent).
     */
    public function upsert(array|string $uniqueBy, ?array $update = null): int
    {
        if ($this->rows === []) {
            return 0;
        }

        $uniqueBy = (array) $uniqueBy;

        if ($uniqueBy === []) {
            throw InvalidSeederOperation::missingUniqueBy();
        }

        if ($update === null) {
            $columns = array_keys($this->rows[0]);
            $update = array_values(array_diff($columns, $uniqueBy));
        }

        return $this->query()->upsert($this->rows, $uniqueBy, $update);
    }
}
