<?php

namespace Iamluc\Script\Node;

class AssignNode extends Node
{
    private $vars;
    private $values;
    private $local;

    public function __construct($variables, $values, bool $local)
    {
        if (!\is_array($variables)) {
            $variables = [$variables];
        }

        if (!\is_array($values)) {
            $values = [$values];
        }

        $this->vars = $variables;
        $this->values = $values;
        $this->local = $local;
    }

    /**
     * @return Node[]
     */
    public function getVariables(): array
    {
        return $this->vars;
    }

    /**
     * @return Node[]
     */
    public function getValues(): array
    {
        return $this->values;
    }

    public function isLocal(): bool
    {
        return $this->local;
    }
}
