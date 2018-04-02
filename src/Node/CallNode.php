<?php

namespace Iamluc\Script\Node;

class CallNode extends Node
{
    private $name;
    private $nodes;

    public function __construct(string $name, array $nodes)
    {
        $this->name = $name;
        $this->nodes = $nodes;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getNodes()
    {
        return $this->nodes;
    }
}
