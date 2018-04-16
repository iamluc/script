<?php

namespace Iamluc\Script\Node;

class ReturnNode extends Node
{
    private $values;

    /**
     * @param Node[] $values
     */
    public function __construct(array $values)
    {
        $this->values = $values;
    }

    public function getValues(): array
    {
        return $this->values;
    }
}
