<?php

namespace Iamluc\Script\Node;

class TableIndexNode extends Node
{
    private $table;
    private $index;

    public function __construct(Node $table, Node $index)
    {
        if (!$table instanceof VariableNode && !$table instanceof TableIndexNode) {
            throw new \LogicException('The "table" argument of TableIndexNode must be a VariableNode or an TableIndexNode.');
        }

        $this->table = $table;
        $this->index = $index;
    }

    /**
     * @return VariableNode|TableIndexNode
     */
    public function getTable(): Node
    {
        return $this->table;
    }

    public function getIndex(): Node
    {
        return $this->index;
    }
}
