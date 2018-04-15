<?php

namespace Iamluc\Script\Node;

class FunctionNode extends Node
{
    private $arguments;
    private $block;

    public function __construct(array $arguments, Node $block)
    {
        $this->arguments = $arguments;
        $this->block = $block;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getBlock(): Node
    {
        return $this->block;
    }
}
