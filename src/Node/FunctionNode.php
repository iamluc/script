<?php

namespace Iamluc\Script\Node;

class FunctionNode extends Node
{
    private $name;
    private $args;
    private $block;
    private $local;

    public function __construct($name, Node $args = null, Node $block, bool $local = false) // FIXME: arguments
    {
        $this->name = $name;
        $this->args = $args;
        $this->block = $block;
        $this->local = $local;
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

    public function isLocal(): bool
    {
        return $this->local;
    }
}
