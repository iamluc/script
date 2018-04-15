<?php

namespace Iamluc\Script\Node;

class ForeachNode extends Node
{
    private $variable;
    private $expression;

    public function __construct(string $variable, Node $expression)
    {
        $this->variable = $variable;
        $this->expression = $expression;
    }

    public function getVariable(): string
    {
        return $this->variable;
    }

    public function getExpression(): Node
    {
        return $this->expression;
    }
}
