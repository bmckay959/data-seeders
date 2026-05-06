<?php

namespace Bmckay959\DataSeeders\Builders;

use Bmckay959\DataSeeders\Exceptions\InvalidSeederOperation;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;

abstract class Builder
{
    protected ?string $table = null;

    protected ?string $connection;

    /**
     * @var array<int, array{0: string, 1: string, 2: mixed}>
     */
    protected array $wheres = [];

    /**
     * @var array<int, array{0: string, 1: array<int, mixed>}>
     */
    protected array $whereIns = [];

    public function __construct(
        protected ConnectionResolverInterface $resolver,
        ?string $connection = null,
    ) {
        $this->connection = $connection;
    }

    /**
     * Target a database table directly.
     */
    public function table(string $table): static
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Target the table associated with the given Eloquent model. The model's
     * connection is also adopted unless one was already set explicitly.
     *
     * @param  class-string|Model  $model
     */
    public function model(string|Model $model): static
    {
        if (is_string($model)) {
            if (! is_subclass_of($model, Model::class)) {
                throw InvalidSeederOperation::notAModel($model);
            }

            $instance = new $model;
        } else {
            $instance = $model;
        }

        $this->table = $instance->getTable();
        $this->connection ??= $instance->getConnectionName();

        return $this;
    }

    /**
     * Add a where clause. Supports `where('col', 'value')` or
     * `where('col', '=', 'value')`.
     */
    public function where(string $column, mixed $operator = null, mixed $value = null): static
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [$column, (string) $operator, $value];

        return $this;
    }

    /**
     * Add a whereIn clause.
     *
     * @param  array<int, mixed>  $values
     */
    public function whereIn(string $column, array $values): static
    {
        $this->whereIns[] = [$column, $values];

        return $this;
    }

    /**
     * Resolve the underlying connection.
     */
    protected function connection(): ConnectionInterface
    {
        return $this->resolver->connection($this->connection);
    }

    /**
     * Build the underlying query builder for the configured table.
     */
    protected function query(): QueryBuilder
    {
        if ($this->table === null) {
            throw InvalidSeederOperation::missingTable(static::class);
        }

        $query = $this->connection()->table($this->table);

        foreach ($this->wheres as [$column, $operator, $value]) {
            $query->where($column, $operator, $value);
        }

        foreach ($this->whereIns as [$column, $values]) {
            $query->whereIn($column, $values);
        }

        return $query;
    }

    /**
     * Execute the operation.
     */
    abstract public function run(): int;
}
