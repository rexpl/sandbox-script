<?php

declare(strict_types=1);

namespace Rexpl\SandBoxScript;

class AllowedMethods
{
    public function __construct(
        protected array $methods,
    ) {}


    /**
     * @param string $class
     * @param array $methods
     *
     * @return $this
     */
    public function addAllowedMethods(string $class, array $methods = []): static
    {
        $this->methods[$class] = $methods;

        return $this;
    }


    /**
     * @return array
     */
    public function getMethodsArray(): array
    {
        return $this->methods;
    }
}