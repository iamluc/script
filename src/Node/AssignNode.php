<?php

namespace Iamluc\Script\Node;


class AssignNode extends Node
{
    private $var;
    private $value;

    public function __construct($var, Node $value)
    {
        $this->var = $var;
        $this->value = $value;
    }

    public function getVariableName()
    {
        return $this->var;
    }

    public function getValue(): Node
    {
        return $this->value;
    }
}
