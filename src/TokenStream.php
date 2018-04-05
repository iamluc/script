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
//            dump($this->tokens[$nextPos]);
        }

        if ($peek) {
            $this->position = $nextPos;
        }

        return $this->tokens[$nextPos];
    }

    public function expect($types)
    {
        return $this->next(false)->is($types);
    }

    public function expectEOF()
    {
        return $this->next(false)->isEOF();
    }

    public function expectScalar()
    {
        return $this->next(false)->isScalar();
    }

    public function expectVariable()
    {
        return $this->next(false)->isVariable();
    }
}
