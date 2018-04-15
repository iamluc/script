<?php

namespace Iamluc\Script\Node;

class CallNode extends Node
{
    private $functionName;
    private $arguments;

    public function __construct(string $functionName, array $arguments)
    {
        $this->functionName = $functionName;
        $this->arguments = $arguments;
    }

    public function getFunctionName()
    {
        return $this->functionName;
    }

    /**
     * @return Node[]
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }
}
