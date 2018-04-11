<?php

namespace Iamluc\Script\Node;

class NegativeNode extends Node
{
    private $value;

    public function __construct(Node $value)
    {
        $this->value = $value;
    }

    public function getValue(): Node
    {
        return $this->value;
    }
}
