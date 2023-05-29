<?php

declare(strict_types=1);

namespace Rexpl\SandBoxScript\Extensions;

use Countable;

class ArrayFunctions
{
    public function keys(array $array): array
    {
        return array_keys($array);
    }

    public function count(array|Countable $array): int
    {
        return count($array);
    }
}