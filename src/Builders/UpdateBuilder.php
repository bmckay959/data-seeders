<?php

namespace Bmckay959\DataSeeders\Builders;

use Bmckay959\DataSeeders\Exceptions\InvalidSeederOperation;

class UpdateBuilder extends Builder
{
    /**
     * @var array<string, mixed>
     */
    protected array $values = [];

    /**
     * Set the columns to update.
     *
     * @param  array<string, mixed>  $columns
     */
    public function columns(array $columns): self
    {
        $this->values = array_merge($this->values, $columns);

        return $this;
    }

    /**
     * Execute the update. Returns the number of affected rows.
     */
    public function run(): int
    {
        if ($this->values === []) {
            throw InvalidSeederOperation::nothingToUpdate();
        }

        return $this->query()->update($this->values);
    }
}
