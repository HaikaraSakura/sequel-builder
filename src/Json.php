<?php

namespace Haikara\SequelBuilder;

use Haikara\SequelBuilder\Builder\Select;

class Json
{
    protected array $json_columns = [];

    protected array $values = [];

    /**
     * @param iterable<string,string|Raw|Select> $columns
     */
    public function __construct(iterable $columns)
    {
        foreach ($columns as $key => $column) {
            if ($column instanceof Raw) {
                $this->json_columns[] = "'$key', $column";
                $this->values = [...$this->values, $column->getValues()];
            } elseif ($column instanceof Select) {
                $this->json_columns[] = "'$key', ($column)";
                $this->values = [...$this->values, $column->getValues()];
            } else {
                $this->json_columns[] = "'$key', $column";
            }
        }
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function __toString(): string
    {
        return 'JSON_ARRAYAGG(JSON_OBJECT(' . join(',', $this->json_columns) . '))';
    }
}