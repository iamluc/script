<?php

namespace Iamluc\Script\Node;

class VariableNode extends Node
{
    private $variable;

    public function __construct(string $variable)
    {
        $this->variable = $variable;
    }

    public function getVariable()
    {
        return $this->variable;
    }
}
