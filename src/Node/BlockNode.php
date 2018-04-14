<?php

namespace Iamluc\Script\Node;

class BlockNode extends Node
{
    private $nodes;
    private $labels;

    public function __construct(iterable $nodes, array $labels = [])
    {
        $this->nodes = $nodes;
        $this->labels = $labels;
    }

    public function getNodes(): iterable
    {
        return $this->nodes;
    }

    public function hasLabel($name): bool
    {
        return \in_array($name, $this->labels, true);
    }
}
