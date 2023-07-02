<?php

namespace Haikara\SequelBuilder\Rules;

use Haikara\SequelBuilder\StringWithValuesInterface;

interface RulesInterface extends StringWithValuesInterface
{
    public function getNodes(): array;

    public function getValues(): array;

    public function hasRule(): bool;
}