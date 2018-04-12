<?php

namespace Iamluc\Script\Node;

class ConditionalNode extends Node
{
    private $condition;
    private $if;
    private $else;

    public function __construct(Node $condition, BlockNode $if, Node $else)
    {
        if (!$else instanceof BlockNode && !$else instanceof ConditionalNode) {
            throw new \LogicException('Argument "else" of ConditionalNode must be a BlockNode or a ConditionalNode.');
        }

        $this->condition = $condition;
        $this->if = $if;
        $this->else = $else;
    }

    public function getCondition(): Node
    {
        return $this->condition;
    }

    public function getIf(): BlockNode
    {
        return $this->if;
    }

    /**
     * @return BlockNode|ConditionalNode
     */
    public function getElse(): Node
    {
        return $this->else;
    }
}
