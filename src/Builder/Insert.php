<?php

declare(strict_types=1);

namespace Haikara\SequelBuilder\Builder;

use Haikara\SequelBuilder\Raw;

use function count;

class Insert extends Builder
{
    protected ?string $select = null;
    protected array $columns = [];
    protected array $update_keys = [];
    protected bool $update_flag = false;
    protected array $rules = [];
    protected array $values_collection = [];

    protected function getType(): string
    {
        return self::INSERT;
    }

    public function values(iterable $record): static
    {
        $values = [];
        $columns = [];
        foreach ($record as $column => $value) {
            if ($this->columns === []) {
                $columns[] = $column;
            }

            if ($value instanceof Raw) {
                $values[] = $value;
                $this->values['values'] = [...$this->values['values'], $value->getValues()];
            } else {
                $values[] = '?';
                $this->values['values'][] = $value;
            }
        }

        if ($this->columns === []) {
            $this->columns = $columns;
        }

        $this->values_collection[] = '(' . join(', ', $values) . ')';

        return $this;
    }

    public function select(Select $select): static
    {
        $this->values['select'] = $select->getValues();
        return $this;
    }

    public function onDuplicateKeyUpdate(): static
    {
        $this->update_flag = true;
        return $this;
    }

    protected function getQuery(): string
    {
        $statements = ["$this->type INTO $this->table"];

        if ($this->columns !== []) {
            $statements[] = '(' . join(', ', $this->columns) . ')';
        }

        if ($this->columns !== []) {
            $statements[] = 'VALUES ' . join(', ', $this->values_collection);
        } elseif ($this->select !== null) {
            $statements[] = $this->select;
        }

        if (count($this->values_collection) !== 1 || !$this->update_flag) {
            return join(' ', $statements);
        }

        if ($this->update_keys === []) {
            // keyValueで値がセットされていなければ、valueでセットされた値をそのまま用いる。
            $this->values['values'] = [...$this->values['values'], ...$this->values['values']];
            $statements[] = 'ON DUPLICATE KEY UPDATE ' . join(' = ?, ', $this->columns) . ' = ?';
        } else {
            $statements[] = 'ON DUPLICATE KEY UPDATE ' . join(' = ?, ', $this->update_keys) . ' = ?';
        }

        return join(' ', $statements);
    }

    public function getValues(): array
    {
        return $this->values !== [] ? $this->values['values'] : $this->values['select'];
    }

    public function hasValues(): bool
    {
        return
            count($this->values['values'] ?? [])
            + count($this->values['select'] ?? [])
            > 0;
    }
}
