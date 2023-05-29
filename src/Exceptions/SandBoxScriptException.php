<?php

declare(strict_types=1);

namespace Rexpl\SandBoxScript\Exceptions;

use Exception;
use Throwable;

class SandBoxScriptException extends Exception
{
    /**
     * @param string $userFriendlyMessage A user-friendly error message to what happened.
     * @param \Throwable|null $previous
     */
    public function __construct(public string $userFriendlyMessage, ?Throwable $previous = null)
    {
        parent::__construct(previous: $previous);
    }
}