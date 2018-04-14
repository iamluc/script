<?php

namespace Iamluc\Script;

class Lexer
{
    private $stream;
    private $cursor;

    private static $stringToToken = [
        'function' => Token::T_FUNCTION,
        'local' => Token::T_LOCAL,

        'if' => Token::T_IF,
        'then' => Token::T_THEN,
        'elseif' => Token::T_ELSEIF,
        'else' => Token::T_ELSE,
        'end' => Token::T_END,

        'while' => Token::T_WHILE,
        'do' => Token::T_DO,

        'return' => Token::T_RETURN,
        'break' => Token::T_BREAK,
        'goto' => Token::T_GOTO,

        'true' => Token::T_TRUE,
        'false' => Token::T_FALSE,
        'nil' => Token::T_NIL,

        'and' => Token::T_AND,
        'or' => Token::T_OR,

        '=' => Token::T_ASSIGN,
        '==' => Token::T_EQUAL,
        '~=' => Token::T_NOT_EQUAL,
        '<' => Token::T_GREATER_THAN,
        '>' => Token::T_GREATER_THAN,
        '<=' => Token::T_LESS_EQUAL,
        '>=' => Token::T_GREATER_EQUAL,

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

        // Ignore comments
        if ($token = $this->match('/--(.*)/A')) {
            return $this->next();
        }

        // Boundary
        if ($token = $this->match('/(and|or|if|then|elseif|else|end|while|do|function|local|return|break|goto)\b/A')) {
            return new Token(self::$stringToToken[$token['match']], $token['match'], $token['cursor']);
        }

        // Labels
        if ($token = $this->match('/::([\w-_]+)::/A')) {
            return new Token(Token::T_LABEL, $token['match'], $token['cursor']);
        }

        // No boundary
        if ($token = $this->match('/(==|~=|<=|<|>=|>|=|\+|-|\*|\/|\(|\))/A')) {
            return new Token(self::$stringToToken[$token['match']], $token['match'], $token['cursor']);
        }

        // Boundary + insensitive
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
