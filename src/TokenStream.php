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

    public function next()
    {
        return $this->forward(false);
    }

    private function forward($peek)
    {
        $nextPos = $this->position + 1;
        if (!isset($this->tokens[$nextPos])) {
            $this->tokens[$nextPos] = $this->lexer->next();
//            dump($this->tokens[$nextPos]);
        }

        if ($peek) {
            $this->position = $nextPos;
        }

        return $this->tokens[$nextPos];
    }

    public function nextIs($types)
    {
        return $this->next()->is($types);
    }

    public function nextIsEOF()
    {
        return $this->next()->isEOF();
    }

    public function nextIsScalar()
    {
        return $this->next()->isScalar();
    }

    public function nextIsVariable()
    {
        return $this->next()->isVariable();
    }
}
