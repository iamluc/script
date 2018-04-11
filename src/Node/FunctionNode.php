<?php

namespace Iamluc\Script\Node;

class FunctionNode extends Node
{
    private $name;
    private $args;
    private $block;

    public function __construct($name, Node $args = null, Node $block) // FIXME: arguments
    {
        $this->name = $name;
        $this->args = $args;
        $this->block = $block;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getArgs(): Node
    {
        return $this->args;
    }

    public function getBlock(): Node
    {
        return $this->block;
    }
}
