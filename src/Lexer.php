<?php

namespace Iamluc\Script;

class Lexer
{
    private $stream;
    private $cursor;

    private static $stringToToken = [
        'if' => Token::T_IF,
        'then' => Token::T_THEN,
        'elseif' => Token::T_ELSEIF,
        'else' => Token::T_ELSE,
        'end' => Token::T_END,

        'true' => Token::T_TRUE,
        'false' => Token::T_FALSE,
        'nil' => Token::T_NIL,

        '=' => Token::T_ASSIGN,
    ];

    public function __construct(string $stream)
    {
        $this->stream = $stream;
        $this->cursor = 0;
    }

    public function all()
    {
        $tokens = [];
        while ($token = $this->next()) {
            $tokens[] = $token;
            if (Token::T_EOF === $token->getType()) {
                break;
            }
        }

        return $tokens;
    }

    public function next()
    {
        if ($this->cursor >= strlen($this->stream)) {
            return new Token(Token::T_EOF);
        }

        // Ignore spaces
        if ($token = $this->match('/(\s+)/A')) {
            return $this->next();
        }

        // Control structures
        if ($token = $this->match('/(if|then|elseif|else|end)\b/A')) {
            return new Token(self::$stringToToken[$token['match']], $token['match'], $token['cursor']);
        }

        // Operators
        if ($token = $this->match('/(=)/A')) {
            return new Token(self::$stringToToken[$token['match']], $token['match'], $token['cursor']);
        }

        // Constants
        if ($token = $this->match('/(true|false|nil)\b/Ai')) {
            $constant = strtolower($token['match']);
            return new Token(self::$stringToToken[$constant], $constant, $token['cursor']);
        }

        // Variables
        if ($token = $this->match('/([\w-_]+)/A')) {
            return new Token(Token::T_NAME, $token['match'], $token['cursor']);
        }

        // Strings
        if ($token = $this->match('/"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\'/As')) {
            return new Token(Token::T_STRING, $token['match'], $token['cursor']);
        }

        throw new \LogicException("Unable to tokenize the stream.");
    }

    private function match($regex)
    {
        if (1 === preg_match($regex, $this->stream, $matches, 0, $this->cursor)) {
            $token = [
                'size' => strlen($matches[0]),
                'full_match' => $matches[0],
                'match' => $matches[1],
                'cursor' => $this->cursor,
            ];

            $this->cursor += $token['size'];

            return $token;
        }

        return false;
    }
}
