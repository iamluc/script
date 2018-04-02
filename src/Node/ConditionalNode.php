<?php

namespace Iamluc\Script\Node;


class ConditionalNode extends Node
{
    private $condition;
    private $if;
    private $else;

    public function __construct(Node $condition, Node $if, Node $else)
    {
        $this->condition = $condition;
        $this->if = $if;
        $this->else = $else;
    }

    public function getCondition(): Node
    {
        return $this->condition;
    }

    public function getIf(): Node
    {
        return $this->if;
    }

    public function getElse(): Node
    {
        return $this->else;
    }
}
