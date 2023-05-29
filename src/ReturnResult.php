<?php

declare(strict_types=1);

namespace Rexpl\SandBoxScript;

class ReturnResult
{
    /**
     * @param string $output STDOUT output.
     * @param mixed $returnValue The return value from the function.
     * @param int $runId The run id of the function.
     */
    public function __construct(
        public string $output,
        public mixed $returnValue,
        public int $runId,
    ) {}
}