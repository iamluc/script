<?php

namespace Iamluc\Script\Node;

class WhileNode extends Node
{
    private $condition;
    private $block;

    public function __construct(Node $condition, BlockNode $block)
    {
        $this->condition = $condition;
        $this->block = $block;
    }

    public function getCondition(): Node
    {
        return $this->condition;
    }

    public function getBlock(): BlockNode
    {
        return $this->block;
    }
}
