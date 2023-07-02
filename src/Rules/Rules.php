<?php

namespace Haikara\SequelBuilder\Rules;

use Closure;
use Haikara\SequelBuilder\Builder\Select;
use Haikara\SequelBuilder\Raw;

use Stringable;

use function mb_ereg_replace;

abstract class Rules implements RulesInterface
{
    use RulesTrait;

    public function when(bool $when, Closure|EveryRules $then, Closure|EveryRules|null $else = null): static
    {
        if (!$when && $else === null) {
            return $this;
        }

        if ($when) {
            $rules = ($then instanceof Closure) ? $then(new EveryRules) : $then;
        } else {
            $rules = ($else instanceof Closure) ? $else(new EveryRules) : $else;
        }

        if ($rules->hasRule()) {
            $this->nodes[] = $rules;
        }

        return $this;
    }

    public function equals(string $column, string|int|float|bool|Raw $value): static
    {
        $this->nodes[] = (new RulesNode)
            ->setLeft(new Raw($column))
            ->setOperator(new Operator('='))
            ->setRight($value);

        return $this;
    }

    public function notEquals(string $column, string|int|float|bool|Raw $value): static
    {
        $this->nodes[] = (new RulesNode)
            ->setLeft(new Raw($column))
            ->setOperator(new Operator('<>'))
            ->setRight($value);

        return $this;
    }

    public function compare(
        string $column,
        string $operator,
        string|int|float|bool|Raw $value
    ): static {
        $this->nodes[] = (new RulesNode)
            ->setLeft(new Raw($column))
            ->setOperator(new Operator($operator))
            ->setRight($value);

        return $this;
    }

    public function like(string $column, string|Raw $value): static
    {
        $this->nodes[] = (new RulesNode)
            ->setLeft(new Raw($column))
            ->setOperator(new Operator('LIKE'))
            ->setRight('%' . static::escWildCard("{$value}") . '%');


        return $this;
    }

    public function likeForward(string $column, string|Raw $value): static
    {
        $this->nodes[] = (new RulesNode)
            ->setLeft(new Raw($column))
            ->setOperator(new Operator('LIKE'))
            ->setRight(static::escWildCard("{$value}") . '%');

        return $this;
    }

    public function likeBackword(string $column, string|Raw $value): static
    {
        $this->nodes[] = (new RulesNode)
            ->setLeft(new Raw($column))
            ->setOperator(new Operator('LIKE'))
            ->setRight('%' . static::escWildCard("{$value}"));

        return $this;
    }

    public function notLike(string $column, string|Raw $value): static
    {
        $this->nodes[] = (new RulesNode)
            ->setLeft(new Raw($column))
            ->setOperator(new Operator('NOT LIKE'))
            ->setRight('%' . static::escWildCard("{$value}") . '%');

        return $this;
    }

    public function notLikeForward(string $column, string|Raw $value): static
    {
        $this->nodes[] = (new RulesNode)
            ->setLeft(new Raw($column))
            ->setOperator(new Operator('NOT LIKE'))
            ->setRight(static::escWildCard("{$value}") . '%');

        return $this;
    }

    public function notLikeBackword(string $column, string|Raw $value): static
    {
        $this->nodes[] = (new RulesNode)
            ->setLeft(new Raw($column))
            ->setOperator(new Operator('NOT LIKE'))
            ->setRight('%' . static::escWildCard("{$value}"));

        return $this;
    }

    public function between(
        string $column,
        int|float|array|string|Stringable|null $left_value,
        int|float|array|string|Stringable|null $right_value
    ): static {
        $this->nodes[] = (new RulesNode)
            ->setLeft(new Raw($column))
            ->setOperator(new Operator('BETWEEN'))
            ->setBETWEEN(new Between($left_value, $right_value));

        return $this;
    }

    public function notBetween(
        string $column,
        int|float|array|string|Stringable|null $left_value,
        int|float|array|string|Stringable|null $right_value
    ): static {
        $this->nodes[] = (new RulesNode)
            ->setLeft(new Raw($column))
            ->setOperator(new Operator('NOT BETWEEN'))
            ->setBETWEEN(new Between($left_value, $right_value));

        return $this;
    }

    public function isNull(string $column): static
    {
        $this->nodes[] = (new RulesNode)
            ->setLeft(new Raw($column))
            ->setOperator(new Operator('IS'))
            ->setRight(null);

        return $this;
    }

    public function isNotNull(string $column): static
    {
        $this->nodes[] = (new RulesNode)
            ->setLeft(new Raw($column))
            ->setOperator(new Operator('IS NOT'))
            ->setRight(null);

        return $this;
    }

    public function in(string $column, array|Select|Raw $values): static
    {
        $this->nodes[] = (new RulesNode)
            ->setLeft(new Raw($column))
            ->setOperator(new Operator('IN'))
            ->setRight($values);

        return $this;
    }

    public function notIn(string $column, array|Select|Raw $values): static
    {
        $this->nodes[] = (new RulesNode)
            ->setLeft(new Raw($column))
            ->setOperator(new Operator('NOT IN'))
            ->setRight($values);

        return $this;
    }

    public function exists(array|Select|Raw $values): static
    {
        $this->nodes[] = (new RulesNode)
            ->setOperator(new Operator('EXISTS'))
            ->setRight($values);

        return $this;
    }

    public function notExists(array|Select|Raw $values): static
    {
        $this->nodes[] = (new RulesNode)
            ->setOperator(new Operator('NOT EXISTS'))
            ->setRight($values);

        return $this;
    }

    public function any(Closure|AnyRules $rules): static
    {
        $any = ($rules instanceof AnyRules) ? $rules : new AnyRules;

        if ($rules instanceof Closure) {
            $rules($any);
        }
        $this->nodes[] = $any;

        return $this;
    }

    public function raw(string|Raw $rule): static
    {
        $this->nodes[] = $rule instanceof Raw ? $rule : new Raw($rule);

        return $this;
    }

    public static function escWildCard(string $value, string $esc_char = '#'): string
    {
        return mb_ereg_replace('([_%#])', $esc_char . '\1', $value);
    }
}