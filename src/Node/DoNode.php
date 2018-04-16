<?php

namespace Iamluc\Script\Node;

class DoNode extends Node
{
    private $block;

    public function __construct(BlockNode $block)
    {
        $this->block = $block;
    }

    public function getBlock(): BlockNode
    {
        return $this->block;
    }
}
