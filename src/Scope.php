<?php

namespace Iamluc\Script;

class Scope
{
    private $variables = [];
    private $functions = [];

    public function setVariable($name, $value)
    {
        return $this->variables[$name] = $value;
    }

    public function hasVariable($name)
    {
        return array_key_exists($name, $this->variables);
    }

    public function getVariable($name)
    {
        return $this->variables[$name] ?? null;
    }

    public function getVariables(): array
    {
        return $this->variables;
    }

    public function setFunction($name, Node\FunctionNode $node)
    {
        $this->functions[$name] = $node;
    }

    public function getFunction($name): Node\FunctionNode
    {
        if (!isset($this->functions[$name])) {
            throw new \LogicException(sprintf('Function "%s" is not defined.', $name));
        }

        return $this->functions[$name];
    }
}
