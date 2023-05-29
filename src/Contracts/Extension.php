<?php

namespace Rexpl\SandBoxScript\Contracts;

use Rexpl\SandBoxScript\AllowedMethods;

interface Extension
{
    /**
     * Returns the namespace in which the extension will operate.
     * The extension will be accessible using ext.{{ namespace }}.{{ method }}
     *
     * @return string
     */
    public function namespace(): string;


    /**
     * Returns the extension object.
     *
     * @return object
     */
    public function boot(): object;


    /**
     * Register all the allowed methods to the sandbox security policy.
     *
     * @param \Rexpl\SandBoxScript\AllowedMethods $allowedMethods
     *
     * @return void
     */
    public function registerMethods(AllowedMethods $allowedMethods): void;
}