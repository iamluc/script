<?php

namespace Iamluc\Script;

class Lexer
{
    private $stream;
    private $cursor;

    private static $stringToToken = [
        'function' => Token::T_FUNCTION,
        'return' => Token::T_RETURN,

        'if' => Token::T_IF,
        'then' => Token::T_THEN,
        'elseif' => Token::T_ELSEIF,
        'else' => Token::T_ELSE,
        'end' => Token::T_END,

        'while' => Token::T_WHILE,
        'do' => Token::T_DO,
        'break' => Token::T_BREAK,

        'true' => Token::T_TRUE,
        'false' => Token::T_FALSE,
        'nil' => Token::T_NIL,

        '=' => Token::T_ASSIGN,
        '==' => Token::T_EQUAL,
        '~=' => Token::T_NOT_EQUAL,

        '+' => Token::T_PLUS,
        '-' => Token::T_MINUS,
        '*' => Token::T_STAR,
        '/' => Token::T_SLASH,

        '(' => Token::T_LEFT_PAREN,
        ')' => Token::T_RIGHT_PAREN,
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

        // Control structures, function
        if ($token = $this->match('/(if|then|elseif|else|end|while|do|function|return|break)\b/A')) {
            return new Token(self::$stringToToken[$token['match']], $token['match'], $token['cursor']);
        }

        // Operators
        if ($token = $this->match('/(==|~=|=|\+|-|\*|\/|\(|\))/A')) {
            return new Token(self::$stringToToken[$token['match']], $token['match'], $token['cursor']);
        }

        // Constants
        if ($token = $this->match('/(true|false|nil)\b/Ai')) {
            $constant = strtolower($token['match']);
            return new Token(self::$stringToToken[$constant], $constant, $token['cursor']);
        }

        // Numbers
        if ($token = $this->match('/([0-9]+)\b/A')) {
            return new Token(Token::T_NUMBER, $token['match'], $token['cursor']);
        }

        // Variables
        if ($token = $this->match('/([\w-_]+)\b/A')) {
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
