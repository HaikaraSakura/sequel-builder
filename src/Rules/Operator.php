<?php

declare(strict_types=1);

namespace Haikara\SequelBuilder\Rules;

use Haikara\SequelBuilder\Exceptions\QueryBuildException;

class Operator
{
    protected const OPERATORS = [
        '=',
        '<=>',
        '<>',
        '!=',
        '>',
        '>=',
        '<',
        '<=',
        'LIKE',
        'NOT LIKE',
        'IS',
        'IS NOT',
        'IN',
        'NOT IN',
        'EXISTS',
        'NOT EXISTS',
        'BETWEEN',
        'NOT BETWEEN',
    ];

    protected string $operator;

    public function __construct(string $operator)
    {
        $operator = strtoupper($operator);

        if (!in_array($operator, static::OPERATORS)) {
            throw new QueryBuildException();
        }

        $this->operator = $operator;
    }

    public function equals(string $operator): bool
    {
        return $operator === $this->operator;
    }

    public function __toString(): string
    {
        return $this->operator;
    }
}
