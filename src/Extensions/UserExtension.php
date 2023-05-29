<?php

declare(strict_types=1);

namespace Rexpl\SandBoxScript\Extensions;

use Rexpl\SandBoxScript\AllowedMethods;
use Twig\Sandbox\SecurityPolicy;

class UserExtension
{
    /**
     * The already booted methods.
     *
     * @var array<string,object>
     */
    protected array $booted = [];


    /**
     * @param array<\Rexpl\SandBoxScript\Contracts\Extension> $extensions
     * @param \Twig\Sandbox\SecurityPolicy $twigPolicy
     * @param \Rexpl\SandBoxScript\AllowedMethods $allowedMethods
     */
    public function __construct(
        protected array $extensions,
        protected SecurityPolicy $twigPolicy,
        protected AllowedMethods $allowedMethods
    ) {}


    /**
     * @param string $name
     *
     * @return object
     */
    public function __get(string $name)
    {
        return $this->booted[$name]
            ?? $this->bootExtension($name);
    }


    /**
     * @param string $name
     *
     * @return object
     */
    protected function bootExtension(string $name): object
    {
        $extension = $this->extensions[$name]->boot();
        $this->booted[$name] = $extension;

        $this->extensions[$name]->registerMethods($this->allowedMethods);

        $this->twigPolicy->setAllowedMethods(
            $this->allowedMethods->getMethodsArray()
        );

        return $extension;
    }


    /**
     * @param string $name
     *
     * @return bool
     *
     * @link https://github.com/twigphp/Twig/issues/601
     */
    public function __isset(string $name): bool
    {
        return isset($this->extensions[$name]);
    }
}