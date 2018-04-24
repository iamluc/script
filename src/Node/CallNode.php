<?php

namespace Iamluc\Script\Node;

class CallNode extends Node
{
    private $function;
    private $arguments;

    public function __construct(Node $function, array $arguments)
    {
        $this->function = $function;
        $this->arguments = $arguments;
    }

    public function getFunction(): Node
    {
        return $this->function;
    }

    /**
     * @return Node[]
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }
}
