<?php

namespace Haikara\SequelBuilder\Rules;

class AnyRules extends Rules
{
    use RulesTrait;

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return '(' . join(' OR ', $this->nodes) . ')';
    }
}
