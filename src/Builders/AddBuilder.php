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
}
