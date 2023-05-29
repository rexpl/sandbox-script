<?php

declare(strict_types=1);

namespace Rexpl\SandBoxScript\Extensions;

class StringFunctions
{
    public function contains(string $haystack, string $needle): bool
    {
        return str_contains($haystack, $needle);
    }

    public function startsWith(string $haystack, string $needle): bool
    {
        return str_starts_with($haystack, $needle);
    }

    public function endsWith(string $haystack, string $needle): bool
    {
        return str_ends_with($haystack, $needle);
    }

    public function replace(array|string $search, array|string $replace, array|string $haystack): string
    {
        return str_replace($search, $replace, $haystack);
    }
}