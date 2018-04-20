<?php

namespace Iamluc\Script;

class Lexer
{
    private $stream;
    private $cursor = 0;
    private $column = 1;
    private $line = 1;

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
        'repeat' => Token::T_REPEAT,
        'until' => Token::T_UNTIL,
        'for' => Token::T_FOR,
        'in' => Token::T_IN,

        'return' => Token::T_RETURN,
        'break' => Token::T_BREAK,
        'goto' => Token::T_GOTO,

        'true' => Token::T_TRUE,
        'false' => Token::T_FALSE,
        'nil' => Token::T_NIL,

        'and' => Token::T_AND,
        'or' => Token::T_OR,
        'not' => Token::T_NOT,

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
        '^' => Token::T_EXP,

        '(' => Token::T_LEFT_PAREN,
        ')' => Token::T_RIGHT_PAREN,
        '{' => Token::T_LEFT_BRACE,
        '}' => Token::T_RIGHT_BRACE,
        ',' => Token::T_COMMA,
        ';' => Token::T_SEMI_COLON,
        '..' => Token::T_DOUBLE_DOT,
    ];

    public function __construct(string $stream)
    {
        $this->stream = $stream;
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
            return new Token(Token::T_EOF, null, $this->line, $this->column);
        }

        // Ignore spaces
        if ($token = $this->match('/(\s+)/A')) {
            return $this->next();
        }

        // Ignore long comments
        if ($token = $this->match('/--\[(?<length>=*)\[(.*)--\]\1\]/As')) {
            return $this->next();
        }

        // Ignore short comments
        if ($token = $this->match('/--(.*)/A')) {
            return $this->next();
        }

        // Keywords
        if ($token = $this->match('/(true|false|nil|and|or|not|if|then|elseif|else|end|while|do|repeat|until|for|in|function|local|return|break|goto)\b/A')) {
            return new Token(self::$stringToToken[$token['match']], $token['match'], $token['line'], $token['column']);
        }

        // Labels
        if ($token = $this->match('/::([\w-_]+)::/A')) {
            return new Token(Token::T_LABEL, $token['match'], $token['line'], $token['column']);
        }

        // Operators and punctuations
        if ($token = $this->match('/(==|~=|<=|<|>=|>|=|\+|-|\*|\/|\(|\)|\.\.|,|{|}|;|\^)/A')) {
            return new Token(self::$stringToToken[$token['match']], $token['match'], $token['line'], $token['column']);
        }

        // Numbers
        if ($token = $this->match('/([0-9]+(?:\.[0-9]+)?)/A')) {
            return new Token(Token::T_NUMBER, $token['match'], $token['line'], $token['column']);
        }

        // Variables
        if ($token = $this->match('/([\w-_]+)/A')) {
            return new Token(Token::T_NAME, $token['match'], $token['line'], $token['column']);
        }

        // Double square brackets strings
        if ($token = $this->match('/\[\[([^\]]*)\]\]/As')) { // FIXME: Improvements needed
            return new Token(Token::T_STRING, stripcslashes($token['match']), $token['line'], $token['column']);
        }

        // Single quotes and double quotes strings
        if ($token = $this->match('/("([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\')/As')) {
            return new Token(Token::T_STRING, stripcslashes(substr($token['match'], 1, -1)), $token['line'], $token['column']);
        }

        throw new \LogicException(sprintf('Unable to tokenize the stream near "%s ..." (line %d, column %d).', substr($this->stream, $this->cursor, 20), $this->line, $this->column));
    }

    private function match($regex)
    {
        if (1 === preg_match($regex, $this->stream, $matches, 0, $this->cursor)) {
            $token = [
                'match' => $matches[1],
                'cursor' => $this->cursor,
                'line' => $this->line,
                'column' => $this->column,
            ];

            $this->cursor += strlen($matches[0]);

            $newLines = substr_count($matches[0], "\n");
            $this->line += $newLines;

            if ($newLines) {
                $this->column = strlen($matches[0]) - strrpos($matches[0], "\n");
            } else {
                $this->column += strlen($matches[0]);
            }

            return $token;
        }

        return false;
    }
}
