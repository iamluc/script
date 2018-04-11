<?php

namespace Iamluc\Script\Node;

class CallNode extends Node
{
    private $functionName;

    public function __construct(string $functionName)
    {
        $this->functionName = $functionName;
    }

    public function getFunctionName()
    {
        return $this->functionName;
    }
}
