<?php

declare(strict_types=1);

namespace Haikara\SequelBuilder\Builder;

use Haikara\SequelBuilder\Rules\EveryRules;

class Select extends Builder
{
    public function buildFoundRows(): Select
    {
        return (clone $this)
            ->columns("COUNT(*) OVER()", ...$this->columns)
            ->limit(1);
    }

    protected function getType(): string
    {
        return self::SELECT;
    }

    public function getValues(): array
    {
        $values = [
            ...$this->values['columns'] ?? [],
            ...$this->values['join'] ?? [],
            ...$this->values['where'] ?? [],
            ...$this->values['having'] ?? []
        ];

        if (isset($this->values['limit'])) {
            $values[] = $this->values['limit'];
        }

        if (isset($this->values['offset'])) {
            $values[] = $this->values['offset'];
        }

        return $values;
    }

    public function hasValues(): bool
    {
        return
            count($this->values['columns'] ?? [])
            + count($this->values['join'] ?? [])
            + count($this->values['where'] ?? [])
            + count($this->values['having'] ?? [])
            + count($this->values['limit'] ?? [])
            + count($this->values['offset'] ?? [])
            > 0;
    }

    protected function getQuery(): string
    {
        $statements = [$this->type];

        $table = isset($this->alias) ? $this->table : $this->alias;
        $statements[] = ($this->columns !== []) ? join(', ', $this->columns) : "$table.*";

        $statements[] = "FROM $this->table";

        if (isset($this->alias)) {
            $statements[] = "AS $this->alias";
        }

        if ($this->join !== []) {
            $statements[] = join(' ', $this->join);
        }

        if ($this->where instanceof EveryRules && $this->where->hasRule()) {
            $statements[] = "WHERE $this->where";
        }

        if ($this->group !== []) {
            $statements[] = "GROUP BY " . join(', ', $this->group);
        }

        if ($this->having instanceof EveryRules && $this->having->hasRule()) {
            $statements[] = "HAVING $this->having";
        }

        if ($this->order !== []) {
            $statements[] = "ORDER BY " . join(', ', $this->order);
        }

        if (isset($this->limit)) {
            $statements[] = "LIMIT $this->limit";
        }

        if (isset($this->offset)) {
            $statements[] = "OFFSET $this->offset";
        }

        return join(' ', $statements);
    }
}
