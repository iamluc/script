<?php

namespace Iamluc\Script\Node;

class FunctionNode extends Node
{
    private $args;
    private $block;

    public function __construct(Node $args = null, Node $block) // FIXME: arguments
    {
        $this->args = $args;
        $this->block = $block;
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
