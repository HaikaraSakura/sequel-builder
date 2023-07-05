<?php

declare(strict_types=1);

namespace Haikara\SequelBuilder\Rules;

use Haikara\SequelBuilder\Builder\Select;
use Haikara\SequelBuilder\Exceptions\QueryBuildException;
use Haikara\SequelBuilder\Raw;
use Haikara\SequelBuilder\StringWithValuesInterface;
use Stringable;

class RulesNode implements StringWithValuesInterface
{
    protected ?Operator $operator;

    protected ?Between $between;

    protected int|float|array|string|Stringable|Raw|Select|null $left_value = null;

    protected int|float|array|string|Stringable|Raw|Select|null $right_value = null;

    protected array $values = [];

    public function __construct()
    {
    }

    public function setLeft(int|float|array|string|Stringable|Raw|Select|null $value): static
    {
        $value ??= new Raw('NULL');

        if (is_array($value)) {
            $this->values = $value;
        } elseif ($value instanceof Select || $value instanceof Raw) {
            $this->values = $value->getValues();
        } else {
            $this->values = [$value];
        }

        $this->left_value = $value;

        return $this;
    }

    public function setRight(int|float|array|string|Stringable|Raw|Select|null $value): static
    {
        $value ??= new Raw('NULL');

        if (is_array($value)) {
            $this->values = $value;
        } elseif ($value instanceof Select || $value instanceof Raw) {
            $this->values = $value->getValues();
        } else {
            $this->values = [$value];
        }

        $this->right_value = $value;

        return $this;
    }

    public function setOperator(Operator $operator): static
    {
        // 別の演算子がセット済みならエラー
        if (isset($this->operator)) {
            throw new QueryBuildException("すでに演算子がセットされています");
        }

        $this->operator = $operator;

        return $this;
    }

    public function setBETWEEN(Between $between): static
    {
        // BETWEEN以外の演算子がセット済みならエラー
        if (!$this->operator->equals('BETWEEN') && !$this->operator->equals('NOT BETWEEN')) {
            throw new QueryBuildException("すでに演算子がセットされています");
        }

        $this->values = [...$this->values, ...$between->getValues()];
        $this->right_value = new Raw($between);

        return $this;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function hasValues(): bool
    {
        return count($this->values) > 0;
    }

    public function __toString(): string
    {
        $segments = [];

        if (isset($this->left_value)) {
            $segments[] = match (true) {
                $this->left_value instanceof Raw => $this->left_value,
                $this->left_value instanceof Select => "($this->left_value)",
                default => '?'
            };
        }

        if (isset($this->operator)) {
            $segments[] = $this->operator;
        }

        if (isset($this->between)) {
            $segments[] = $this->between;
            return join(' ', $segments);
        }

        if (isset($this->right_value)) {
            $segments[] = match (true) {
                $this->right_value instanceof Raw => $this->right_value,
                $this->right_value instanceof Select => "($this->right_value)",
                is_array($this->right_value) => '(' . join(', ', array_fill(0, count($this->right_value), '?')) . ')',
                default => '?'
            };
        }

        return join(' ', $segments);
    }
}
