<?php

namespace Iamluc\Script\Node;

class TableNode extends Node
{
    private $fields;

    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    public function getFields(): array
    {
        return $this->fields;
    }
}
