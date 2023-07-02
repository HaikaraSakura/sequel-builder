<?php

declare(strict_types=1);

namespace Haikara\SequelBuilder\Builder;

class Delete extends Builder
{
    protected function getType(): string
    {
        return self::DELETE;
    }

    protected function getQuery(): string
    {
        $statements = ["$this->type FROM $this->table"];

        if ($this->join !== []) {
            $statements[] = join(' ', $this->join);
        }

        if (isset($this->where) && $this->where->hasRule()) {
            $statements[] = "WHERE $this->where";
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

    public function getValues(): array
    {
        $values = $this->values['where'] ?? [];

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
            count($this->values['where'] ?? [])
            + count($this->values['limit'] ?? [])
            + count($this->values['offset'] ?? [])
            > 0;
    }

}
