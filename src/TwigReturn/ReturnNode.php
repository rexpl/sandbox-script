<?php

declare(strict_types=1);

namespace Rexpl\SandBoxScript\TwigReturn;

use Twig\Node\Node;

class ReturnNode extends Node
{
    public function __construct($name, \Twig\Node\Expression\AbstractExpression $value, $line, $tag = null)
    {
        parent::__construct(['value' => $value], ['name' => $name], $line, $tag);
    }

    public function compile(\Twig\Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write('\Rexpl\SandBoxScript\Runtime::return($context[\'runId\'], ')
            ->subcompile($this->getNode('value'))
            ->raw(");return;\n")
        ;
    }
}