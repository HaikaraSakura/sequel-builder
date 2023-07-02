<?php

namespace Haikara\SequelBuilder;

use Stringable;

interface StringWithValuesInterface extends Stringable
{
    public function getValues(): array;

    public function hasValues(): bool;
}