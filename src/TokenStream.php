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

    public function peek(): Token
    {
        return $this->forward(true);
    }

    public function next(): Token
    {
        return $this->forward(false);
    }

    private function forward($peek): Token
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

    public function expect($types): Token
    {
        return $this->peek()->expect($types);
    }

    public function nextIs($types): bool
    {
        return $this->next()->is($types);
    }

    public function nextIsEOF(): bool
    {
        return $this->next()->isEOF();
    }

    public function nextIsScalar(): bool
    {
        return $this->next()->isScalar();
    }

    public function nextIsVariable(): bool
    {
        return $this->next()->isVariable();
    }
}
