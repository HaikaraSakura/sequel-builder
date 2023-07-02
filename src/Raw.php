<?php

declare(strict_types=1);

namespace Haikara\SequelBuilder;

use Haikara\SequelBuilder\Exceptions\QueryBuildException;
use Stringable;

class Raw implements StringWithValuesInterface
{
    protected array $values = [];

    public function __construct(protected string|Stringable $statement)
    {
        if ($statement === '') {
            throw new QueryBuildException();
        }
    }

    public function __toString(): string
    {
        return "$this->statement";
    }

    public function bind(mixed $value): void
    {
        $this->values[] = $value;
    }

    public function hasValues(): bool
    {
        return count($this->values) > 0;
    }

    public function getValues(): array
    {
        return $this->values;
    }
}
