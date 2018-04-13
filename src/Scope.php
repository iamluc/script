<?php

namespace Iamluc\Script;

class Scope
{
    private $variables = [];

    public function hasVariable($name): bool
    {
        return array_key_exists($name, $this->variables);
    }

    public function setVariable($name, $value): void
    {
        $this->variables[$name] = $value;
    }

    public function getVariable($name)
    {
        return $this->variables[$name] ?? null;
    }

    public function getVariables(): array
    {
        return $this->variables;
    }
}
