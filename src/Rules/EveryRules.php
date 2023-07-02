<?php

namespace Haikara\SequelBuilder\Rules;

class EveryRules extends Rules
{
    use RulesTrait;

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return join(' AND ', $this->nodes);
    }
}