<?php

namespace Iamluc\Script;

class Token
{
    public const T_STRING = 'string';
    public const T_NUMBER = 'number';
    public const T_TRUE = 'true';
    public const T_FALSE = 'false';
    public const T_NIL = 'nil';

    public const T_FUNCTION = 'function';
    public const T_RETURN = 'return';

    public const T_IF = 'if';
    public const T_THEN = 'then';
    public const T_ELSEIF = 'elseif';
    public const T_ELSE = 'else';
    public const T_END = 'end';

    public const T_WHILE = 'while';
    public const T_DO = 'do';

    public const T_ASSIGN = 'assign';                   // =

    public const T_PLUS = 'plus';                       // +
    public const T_MINUS = 'minus';                     // -
    public const T_STAR = 'star';                       // *
    public const T_SLASH = 'slash';                     // /

    public const T_EQUAL = 'equal';                     // ==
    public const T_NOT_EQUAL = 'not equal';             // ~=

    public const T_LEFT_PAREN = 'left parenthesis';     // (
    public const T_RIGHT_PAREN = 'right parenthesis';   // )

    public const T_NAME = 'name';

    public const T_EOF = 'end of file';

    private $type;
    private $value;
    private $position;

    public function __construct($type, $value = null, $position = null)
    {
        $this->type = $type;
        $this->value = $value;
        $this->position = $position;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function expect($types): self
    {
        if (!$this->is($types)) {
            throw new \LogicException(sprintf('Expected "%s", got "%s"', implode(', ', (array) $types), $this->getType()));
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

    public function isVariable(): bool
    {
        return self::T_NAME === $this->type;
    }

    public function isScalar(): bool
    {
        return $this->is([self::T_STRING, self::T_NUMBER, self::T_TRUE, self::T_FALSE, self::T_NIL]);
    }

    public function isOperator(): bool
    {
        return $this->is([self::T_EQUAL, self::T_NOT_EQUAL]);
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
}
