<?php

namespace Iamluc\Script\Node\FunctionDefinition;

use Iamluc\Script\Node\Node;

class PhpFunctionNode extends Node implements FunctionDefinitionInterface
{
    private $arguments;
    private $callable;

    public function __construct(array $arguments, callable $callable)
    {
        $this->arguments = $arguments;
        $this->callable = $callable;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getCallable(): callable
    {
        return $this->callable;
    }
}
