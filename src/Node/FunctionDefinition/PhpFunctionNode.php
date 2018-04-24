<?php

namespace Iamluc\Script\Node\FunctionDefinition;

use Iamluc\Script\Node\Node;

class PhpFunctionNode extends Node implements FunctionDefinitionInterface
{
    private $callable;

    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    public function getCallable(): callable
    {
        return $this->callable;
    }
}
