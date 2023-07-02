<?php

declare(strict_types=1);

namespace Haikara\SequelBuilder\Rules;

use Haikara\SequelBuilder\Raw;

trait RulesTrait
{
    /**
     * @var RulesNode|RulesInterface|Raw[]
     */
    protected array $nodes;

    public function getNodes(): array
    {
        return $this->nodes;
    }

    public function getValues(): array
    {
        $values = [];

        foreach ($this->nodes as $node) {
            $values = [...$values, ...$node->getValues()];
        }

        return $values;
    }

    public function hasValues(): bool
    {
        foreach ($this->nodes as $node) {
            if ($node->hasValue()) {
                return true;
            }
        }

        return false;
    }

    public function hasRule(): bool
    {
        return count($this->nodes) > 0;
    }
}
