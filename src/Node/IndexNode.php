<?php

namespace Iamluc\Script\Node;

class IndexNode extends Node
{
    private $variable;
    private $key;

    public function __construct(string $variable, Node $key)
    {
        $this->variable = $variable;
        $this->key = $key;
    }

    public function getVariable(): string
    {
        return $this->variable;
    }

    public function getKey(): Node
    {
        return $this->key;
    }
}
