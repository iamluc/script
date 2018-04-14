<?php

namespace Iamluc\Script\Node;

class BinaryNode extends Node
{
    private $left;
    private $operator;
    private $right;

    public function __construct(Node $left, string $operator, Node $right)
    {
        $this->left = $left;
        $this->operator = $operator;
        $this->right = $right;
    }

    public function getLeft(): Node
    {
        return $this->left;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getRight(): Node
    {
        return $this->right;
    }
}
