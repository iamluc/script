<?php

namespace Iamluc\Script\Node;

class BlockNode extends Node
{
    private $nodes;

    public function __construct(iterable $nodes)
    {
        $this->nodes = $nodes;
    }

    public function getNodes(): iterable
    {
        return $this->nodes;
    }
}
