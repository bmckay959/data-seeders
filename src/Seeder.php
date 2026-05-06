<?php

namespace Bmckay959\DataSeeders;

use Bmckay959\DataSeeders\Builders\AddBuilder;
use Bmckay959\DataSeeders\Builders\DeleteBuilder;
use Bmckay959\DataSeeders\Builders\UpdateBuilder;
use Illuminate\Database\ConnectionResolverInterface;

class Seeder
{
    public function __construct(
        protected ConnectionResolverInterface $resolver,
        protected ?string $connection = null,
    ) {}

    /**
     * Switch the connection used by builders created from this seeder.
     */
    public function connection(?string $connection): self
    {
        $clone = clone $this;
        $clone->connection = $connection;

        return $clone;
    }

    /**
     * Begin an insert operation.
     */
    public function add(): AddBuilder
    {
        return new AddBuilder($this->resolver, $this->connection);
    }

    /**
     * Begin an update operation.
     */
    public function update(): UpdateBuilder
    {
        return new UpdateBuilder($this->resolver, $this->connection);
    }

    /**
     * Begin a delete operation.
     */
    public function delete(): DeleteBuilder
    {
        return new DeleteBuilder($this->resolver, $this->connection);
    }
}
