<?php

namespace Bmckay959\DataSeeders\Exceptions;

use InvalidArgumentException;

class InvalidSeederOperation extends InvalidArgumentException
{
    public static function missingTable(string $builder): self
    {
        $short = class_basename($builder);

        return new self("A target table must be set on {$short} via table() or model() before calling run().");
    }

    public static function notAModel(mixed $given): self
    {
        $type = is_object($given) ? $given::class : gettype($given);

        return new self("model() expects an Eloquent model class-string or instance; got {$type}.");
    }

    public static function invalidRow(): self
    {
        return new self('columns() expects an associative array of one row, or a list of associative rows.');
    }

    public static function nothingToUpdate(): self
    {
        return new self('Cannot run an update without any columns. Call columns([...]) first.');
    }
}
