<?php

declare(strict_types=1);

namespace Haikara\SequelBuilder\Rules;

use Haikara\SequelBuilder\Raw;
use Stringable;

class Between
{
    public function __construct(
        protected int|float|array|string|Stringable|null $left_value,
        protected int|float|array|string|Stringable|null $right_value
    ) {
    }

    public function getValues(): array
    {
        $values = [];

        $values[] = $this->left_value instanceof Raw && $this->left_value->hasValues()
            ? $this->left_value->getValues()[0]
            : $this->left_value;

        $values[] = $this->right_value instanceof Raw && $this->right_value->hasValues()
            ? $this->right_value->getValues()[0]
            : $this->right_value;

        return $values;
    }

    public function __toString()
    {
        $segments[] = $this->left_value instanceof Raw
            ? $this->left_value
            : '?';

        $segments[] = 'AND';

        $segments[] = $this->right_value instanceof Raw
            ? $this->right_value
            : '?';

        return join(' ', $segments);
    }
}
