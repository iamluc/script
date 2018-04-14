<?php

namespace Iamluc\Script\Node;

class ForNode extends Node
{
    private $variable;
    private $initial;
    private $limit;
    private $step;
    private $block;

    public function __construct($variable, BlockNode $block, Node $initial, Node $limit, Node $step = null)
    {
        $this->variable = $variable;
        $this->block = $block;
        $this->initial = $initial;
        $this->limit = $limit;
        $this->step = $step;
    }

    public function getVariable()
    {
        return $this->variable;
    }

    public function getBlock(): BlockNode
    {
        return $this->block;
    }

    public function getInitial(): Node
    {
        return $this->initial;
    }

    public function getLimit(): Node
    {
        return $this->limit;
    }

    /**
     * @return Node|null
     */
    public function getStep()
    {
        return $this->step;
    }
}
