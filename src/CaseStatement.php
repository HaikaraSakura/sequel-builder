<?php

declare(strict_types=1);

namespace Haikara\SequelBuilder;

use Haikara\SequelBuilder\Rules\EveryRules;
use Stringable;

use function is_callable;
use function join;

class CaseStatement implements StringWithValuesInterface
{
    protected array $values = [];
    protected array $statements = [];

    public function __construct(mixed $compare_target = null)
    {
        $this->statements[] = 'CASE';

        if ($compare_target instanceof Raw) {
            $this->statements[] = $compare_target;
            $this->values = [...$this->values, ...$compare_target->getValues()];
        } elseif ($compare_target !== null) {
            $this->statements[] = '?';
            $this->values[] = $compare_target;
        }
    }

    public function whenThen(
        callable|EveryRules|Raw|string|int|float|null|Stringable $rules,
        Raw|string|int|float|null|Stringable $value
    ): static {
        if (is_callable($rules)) {
            $rules = $rules(new EveryRules);
        }

        if ($rules instanceof EveryRules || $rules instanceof Raw) {
            $when = $rules;
            $this->values = [...$this->values, ...$rules->getValues()];
        } else {
            $when = '?';
            $this->values[] = $rules;
        }

        if ($value instanceof Raw) {
            $then = $value;
            $this->values = [...$this->values, ...$value->getValues()];
        } else {
            $then = '?';
            $this->values[] = $value;
        }

        $this->statements[] = "WHEN $when THEN $then";

        return $this;
    }

    public function else($value): static
    {
        $this->statements[] = "ELSE ?";
        $this->values[] = $value;

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
        $this->statements[] = 'END';

        return join(' ', $this->statements);
    }
}
