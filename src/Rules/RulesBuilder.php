<?php

namespace Haikara\SequelBuilder\Rules;

class RulesBuilder
{
    public function buildBoth(Rules $rules): string {
        return join(' AND ', $rules->getNodes());
    }
}