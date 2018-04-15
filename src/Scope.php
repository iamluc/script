<?php

namespace Iamluc\Script;

class Scope
{
    private $variables;

    public function __construct(array $variables = [])
    {
        $this->variables = $variables;
    }

    public function hasVariable($name): bool
    {
        return array_key_exists($name, $this->variables);
    }

    public function setVariable($name, $value): void
    {
        $this->variables[$name] = $value;
    }

    public function setVariables(array $variables): void
    {
        $this->variables = $variables + $this->variables;
    }

    public function getVariable($name)
    {
        return $this->variables[$name] ?? null;
    }

    public function getVariables($withFunctions = false): array
    {
        if ($withFunctions) {
            return $this->variables;
        }

        return array_filter($this->variables, function ($val) {
            return !$val instanceof Node\FunctionDefinition\FunctionDefinitionInterface;
        });
    }
}
