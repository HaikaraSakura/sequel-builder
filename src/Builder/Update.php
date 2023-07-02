<?php

declare(strict_types=1);

namespace Haikara\SequelBuilder\Builder;

use Haikara\SequelBuilder\CaseStatement;
use Haikara\SequelBuilder\Exceptions\QueryBuildException;
use Haikara\SequelBuilder\Raw;

class Update extends Builder
{
    protected array $sets = [];

    protected function getType(): string
    {
        return self::UPDATE;
    }

    public function set(string $name, mixed $value): static
    {
        if ($value instanceof Raw || $value instanceof CaseStatement) {
            $this->sets[] = "$name = $value";
            $this->values['sets'] = [...$this->values['sets'] ?? [], ...$value->getValues()];
        } else {
            $this->sets[] = "$name = ?";
            $this->values['sets'][] = $value;
        }

        return $this;
    }

    public function sets(iterable $values): static
    {
        foreach ($values as $name => $value) {
            $this->set($name, $value);
        }

        return $this;
    }

    protected function getQuery(): string
    {
        if ($this->sets === []) {
            throw new QueryBuildException('更新する値をセットしてください。');
        }

        $statements = ["$this->type $this->table SET " . join(', ', $this->sets)];

        if ($this->join !== []) {
            $statements[] = join(' ', $this->join);
        }

        if ($this->where !== null && $this->where->hasRule()) {
            $statements[] = "WHERE $this->where";
        }

        if ($this->group !== []) {
            $statements[] = "GROUP BY " . join(', ', $this->group);
        }

        if ($this->having !== null && $this->having->hasRule()) {
            $statements[] = "HAVING $this->having";
        }

        if ($this->order !== []) {
            $statements[] = "ORDER BY " . join(', ', $this->order);
        }

        if ($this->limit !== null) {
            $statements[] = "LIMIT $this->limit";
        }

        if ($this->offset !== null) {
            $statements[] = "OFFSET $this->offset";
        }

        return join(' ', $statements);
    }

    public function getValues(): array
    {
        $values = [
            ...$this->values['sets'] ?? [],
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
            count($this->values['sets'] ?? [])
            + count($this->values['join'] ?? [])
            + count($this->values['where'] ?? [])
            + count($this->values['having'] ?? [])
            + count($this->values['limit'] ?? [])
            + count($this->values['offset'] ?? [])
            > 0;
    }
}