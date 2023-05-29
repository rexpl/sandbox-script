<?php

declare(strict_types=1);

namespace Rexpl\SandBoxScript\Extensions;

class DebugFunctions
{
    public function dump(mixed $value): void
    {
        dump($value);
    }


    public function dd(mixed $value): void
    {
        dd($value);
    }


    public function varDump($value)
    {
        var_dump($value);
    }
}