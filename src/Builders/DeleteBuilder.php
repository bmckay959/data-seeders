<?php

namespace Bmckay959\DataSeeders\Builders;

class DeleteBuilder extends Builder
{
    /**
     * Execute the delete. Returns the number of rows deleted.
     */
    public function run(): int
    {
        return $this->query()->delete();
    }
}
