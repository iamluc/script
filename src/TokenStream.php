<?php

namespace Iamluc\Script;

class TokenStream
{
    private $lexer;

    private $position = -1;
    private $tokens = [];

    public function __construct(Lexer $lexer)
    {
        $this->lexer = $lexer;
    }

    public function next($peek = true): Token
    {
        $nextPos = $this->position + 1;
        if (!isset($this->tokens[$nextPos])) {
            $this->tokens[$nextPos] = $this->lexer->next();
        }

        if ($peek) {
            $this->position = $nextPos;
        }

        return $this->tokens[$nextPos];
    }
}
