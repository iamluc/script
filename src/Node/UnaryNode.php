<?php

namespace Iamluc\Script\Node;

class UnaryNode extends Node
{
    private $value;
    private $operator;

    public function __construct(Node $value, string $operator)
    {
        $this->value = $value;
        $this->operator = $operator;
    }

    public function getValue(): Node
    {
        return $this->value;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }
}
