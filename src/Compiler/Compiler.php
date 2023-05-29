<?php

declare(strict_types=1);

namespace Rexpl\SandBoxScript\Compiler;

use Doctrine\Common\Lexer\Token;
use Rexpl\SandBoxScript\Exceptions\CompileException;

class Compiler
{
    /**
     * The lexer for the SandBox Language.
     *
     * @var \Rexpl\SandBoxScript\Compiler\Lexer
     */
    protected Lexer $lexer;


    /**
     * @param array $extensions The loaded extensions.
     */
    public function __construct(array $extensions)
    {
        $this->lexer = new Lexer($extensions);
    }


    /**
     * Compile a given input to twig.
     *
     * @param string $input
     *
     * @return string
     */
    public function compile(string $input): string
    {
        $output = '';

        $this->lexer->setInput($input);
        $this->lexer->moveNext();

        while (true) {

            if (!$this->lexer->lookahead) break;
            $this->lexer->moveNext();

            $output .= $this->processToken($this->lexer->token);
        }

        $this->verifyDoesNotReassignRunId($output);

        return $output;
    }


    protected function processToken(Token $token): string
    {
        return match ($token->type) {
            Lexer::T_IGNORE => '',
            Lexer::T_OPEN => '{% ' . $token->value,
            Lexer::T_CLOSE => ' %}',
            Lexer::T_KEEP => $token->value,
            Lexer::T_DIRECT_EXTENSION_ACCESS => '{% do ' . $token->value,
            Lexer::T_TAB => "\t",
            Lexer::T_LINE_SEPARATOR => PHP_EOL,
            Lexer::T_SPACE => ' ',
        };
    }


    protected function verifyDoesNotReassignRunId(string $compiledFunction): void
    {
        if (!str_contains($compiledFunction, 'runId =')) return;

        throw new CompileException('Cannot reassign "runId".');
    }
}