<?php

namespace Bmckay959\DataSeeders;

use Bmckay959\DataSeeders\Builders\AddBuilder;
use Bmckay959\DataSeeders\Builders\DeleteBuilder;
use Bmckay959\DataSeeders\Builders\UpdateBuilder;
use Closure;
use Illuminate\Database\Connection;
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

    /**
     * Escape hatch for anything the fluent builders don't cover.
     *
     * Pass a SQL string (with optional positional bindings) to run a single
     * statement, or pass a Closure to receive the underlying database
     * connection for full manual control. The closure runs inside the same
     * transaction the runner has already opened around `seed()` / `rollback()`.
     *
     * @param  string|Closure(Connection): mixed  $sqlOrCallback
     * @param  array<int, mixed>  $bindings
     * @return mixed bool when given a SQL string; the callback's return value otherwise.
     */
    public function raw(string|Closure $sqlOrCallback, array $bindings = []): mixed
    {
        $connection = $this->resolveConnection();

        if ($sqlOrCallback instanceof Closure) {
            return $sqlOrCallback($connection);
        }

        return $connection->statement($sqlOrCallback, $bindings);
    }

    /**
     * Resolve the underlying database connection for this seeder.
     */
    public function getConnection(): Connection
    {
        return $this->resolveConnection();
    }

    protected function resolveConnection(): Connection
    {
        $connection = $this->resolver->connection($this->connection);

        if (! $connection instanceof Connection) {
            throw new \RuntimeException('Expected an Illuminate\\Database\\Connection instance.');
        }

        return $connection;
    }
}
