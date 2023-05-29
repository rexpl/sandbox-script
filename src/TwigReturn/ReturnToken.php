<?php

declare(strict_types=1);

namespace Rexpl\SandBoxScript\TwigReturn;

use Twig\Error\SyntaxError;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

class ReturnToken extends AbstractTokenParser
{
    /**
     * Parses a token and returns a node.
     *
     * @return Node
     *
     * @throws SyntaxError
     */
    public function parse(Token $token): Node
    {
        $parser = $this->parser;
        $stream = $parser->getStream();

        $value = $parser->getExpressionParser()->parseExpression();
        $stream->expect(\Twig\Token::BLOCK_END_TYPE);

        return new ReturnNode('return', $value, $token->getLine(), $this->getTag());
    }


    /**
     * Gets the tag name associated with this token parser.
     *
     * @return string
     */
    public function getTag(): string
    {
        return 'return';
    }
}