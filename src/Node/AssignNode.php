<?php

namespace Iamluc\Script\Node;

class AssignNode extends Node
{
    private $var;
    private $value;
    private $local;

    public function __construct($var, Node $value, bool $local)
    {
        $this->var = $var;
        $this->value = $value;
        $this->local = $local;
    }

    public function getVariableName()
    {
        return $this->var;
    }

    public function getValue(): Node
    {
        return $this->value;
    }

    public function isLocal(): bool
    {
        return $this->local;
    }
}
