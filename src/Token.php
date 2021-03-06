<?php

namespace Iamluc\Script;

/**
 * @see https://hackage.haskell.org/package/language-lua-0.10.0/docs/Language-Lua-Token.html
 */
class Token
{
    public const T_STRING = 'string';
    public const T_NUMBER = 'number';
    public const T_TRUE = 'true';
    public const T_FALSE = 'false';
    public const T_NIL = 'nil';

    public const T_FUNCTION = 'function';
    public const T_LOCAL = 'local';

    public const T_IF = 'if';
    public const T_THEN = 'then';
    public const T_ELSEIF = 'elseif';
    public const T_ELSE = 'else';
    public const T_END = 'end';

    public const T_WHILE = 'while';
    public const T_DO = 'do';
    public const T_REPEAT = 'repeat';
    public const T_UNTIL = 'until';
    public const T_FOR = 'for';
    public const T_IN = 'in';

    public const T_RETURN = 'return';
    public const T_BREAK = 'break';
    public const T_GOTO = 'goto';
    public const T_LABEL = 'label';

    public const T_ASSIGN = 'assign';                   // =

    public const T_PLUS = 'plus';                       // +
    public const T_MINUS = 'minus';                     // -
    public const T_STAR = 'star';                       // *
    public const T_SLASH = 'slash';                     // /
    public const T_EXP = 'exponent';                    // ^

    public const T_EQUAL = 'equal';                     // ==
    public const T_NOT_EQUAL = 'not equal';             // ~=
    public const T_LESS_THAN = 'less than';             // <
    public const T_LESS_EQUAL = 'less or equal';        // <=
    public const T_GREATER_THAN = 'greater than';       // >
    public const T_GREATER_EQUAL = 'greater or equal';  // >=

    public const T_AND = 'and';
    public const T_OR = 'or';
    public const T_NOT = 'not';

    public const T_LEFT_PAREN = 'left parenthesis';     // (
    public const T_RIGHT_PAREN = 'right parenthesis';   // )
    public const T_LEFT_BRACE = 'left brace';           // {
    public const T_RIGHT_BRACE = 'right brace';         // }
    public const T_LEFT_BRACKET = 'left bracket';       // [
    public const T_RIGHT_BRACKET = 'right bracket';     // ]

    public const T_COMMA = 'comma';                     // ,
    public const T_SEMI_COLON = 'semi colon';           // ;
    public const T_DOT = 'dot';                         // .
    public const T_DOUBLE_DOT = 'double dot';           // ..

    public const T_NAME = 'name';

    public const T_EOF = 'end of file';

    private $type;
    private $value;
    private $line;
    private $column;

    public function __construct($type, $value = null, int $line = -1, int $column = -1)
    {
        $this->type = $type;
        $this->value = $value;
        $this->line = $line;
        $this->column = $column;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getLine(): int
    {
        return $this->line;
    }

    public function getColumn(): int
    {
        return $this->column;
    }

    public function expect($types): self
    {
        if (!$this->is($types)) {
            throw new \LogicException(sprintf('Expected "%s", got "%s" (line %d, column %d)', implode(', ', (array) $types), $this->getType(), $this->line, $this->column));
        }

        return $this;
    }

    public function is($types): bool
    {
        return \in_array($this->type, (array) $types, true);
    }

    public function isEOF(): bool
    {
        return self::T_EOF === $this->type;
    }

    public function isName(): bool
    {
        return self::T_NAME === $this->type;
    }

    public function isScalar(): bool
    {
        return $this->is([self::T_STRING, self::T_NUMBER, self::T_TRUE, self::T_FALSE, self::T_NIL]);
    }

    public function isComparator(): bool
    {
        return $this->is([self::T_EQUAL, self::T_NOT_EQUAL, self::T_LESS_THAN, self::T_LESS_EQUAL, self::T_GREATER_THAN, self::T_GREATER_EQUAL]);
    }

    public function isMathOperator(): bool
    {
        return $this->is([self::T_PLUS, self::T_MINUS, self::T_STAR, self::T_SLASH]);
    }

    public function getScalarValue()
    {
        if (!$this->isScalar()) {
            throw new \LogicException(sprintf('Token of type "%s" is not a scalar.', $this->type));
        }

        switch ($this->type) {
            case self::T_TRUE:
                return true;

            case self::T_FALSE:
                return false;

            case self::T_NIL:
                return null;

            case self::T_NUMBER:
                return (float) $this->value; // FIXME: float ?
        }

        return $this->value;
    }

    public function __toString()
    {
        return sprintf('"%s" (line %d, column %d)', $this->type, $this->line, $this->column);
    }
}
