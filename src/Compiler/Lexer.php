<?php

declare(strict_types=1);

namespace Rexpl\SandBoxScript\Compiler;

use Doctrine\Common\Lexer\AbstractLexer;

class Lexer extends AbstractLexer
{
    public const T_LINE_SEPARATOR = 0;
    public const T_SPACE = 1;
    public const T_TAB = 2;

    public const T_CLOSE = 100;
    public const T_OPEN = 101;

    public const T_DIRECT_EXTENSION_ACCESS = 200;

    public const T_KEEP = 300;
    public const T_IGNORE = 301;


    /**
     * @var bool
     */
    protected bool $isOpen = false;


    protected string $previousMatch = '';


    /**
     * @param array $extensions The loaded extensions.
     */
    public function __construct(protected array $extensions)
    {
        $this->extensions = array_map(fn ($value) => $value . '.', $this->extensions);
    }


    /**
     * Lexical catchable patterns.
     *
     * @return string[]
     */
    protected function getCatchablePatterns(): array
    {
        return [
            '[;]',
            '[\r\n|\r|\n]',
            '[\t]',
            '[\s]',
        ];
    }

    /**
     * Lexical non-catchable patterns.
     *
     * @return string[]
     */
    protected function getNonCatchablePatterns(): array
    {
        return [
            '[*]',
        ];
    }

    /**
     * Retrieve token type. Also processes the token value if necessary.
     *
     * @return T|null
     *
     * @param-out V $value
     */
    protected function getType(string &$value): int
    {
        $type = $this->isOpen
            ? $this->getTypeIfOpen($value)
            : $this->getTypeIfClosed($value);

        $this->previousMatch = $value;

        return $type;
    }


    protected function getTypeIfOpen(&$value): int
    {
        if ($value === ';') {

            $this->isOpen = false;
            return self::T_CLOSE;
        }

        return self::T_KEEP;
    }


    protected function isAccessingExtension($value): bool
    {
        if (!str_contains($value, '.')) return false;

        foreach ($this->extensions as $ext) {

            if (str_starts_with($value, $ext)) return true;
        }

        return false;
    }


    protected function getTypeIfClosed(&$value): int
    {
        if (!in_array($value, ["\r", "\n", "\t", " "])) {

            $this->isOpen = true;
            return $this->isAccessingExtension($value) ? self::T_DIRECT_EXTENSION_ACCESS : self::T_OPEN;
        }

        if (
            $this->previousMatch === "\r"
            && $value === "\n"
        ) return self::T_IGNORE;

        return match ($value) {
            "\r", "\n" => self::T_LINE_SEPARATOR,
            " " => self::T_SPACE,
            "\t" => self::T_TAB,
        };
    }
}