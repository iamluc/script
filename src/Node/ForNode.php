<?php

namespace Iamluc\Script\Node;

class ForNode extends Node
{
    private $variable;
    private $initial;
    private $limit;
    private $step;
    private $block;

    public function __construct(string $variable, BlockNode $block, Node $initial, Node $limit, Node $step)
    {
        $this->variable = $variable;
        $this->block = $block;
        $this->initial = $initial;
        $this->limit = $limit;
        $this->step = $step;
    }

    public function getVariable(): string
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

    public function getStep(): Node
    {
        return $this->step;
    }
}
