<?php

namespace Iamluc\Script\Node;

class ForInNode extends Node
{
    private $indexVariable;
    private $expressionList;
    private $valueVariable;
    private $block;

    public function __construct(string $indexVariable, string $valueVariable = null, array $expressionList, BlockNode $block)
    {
        $this->indexVariable = $indexVariable;
        $this->expressionList = $expressionList;
        $this->valueVariable = $valueVariable;
        $this->block = $block;
    }

    public function getIndexVariable(): string
    {
        return $this->indexVariable;
    }

    public function getValueVariable(): ?string
    {
        return $this->valueVariable;
    }

    /**
     * @return Node[]
     */
    public function getExpressionList(): array
    {
        return $this->expressionList;
    }

    public function getBlock(): BlockNode
    {
        return $this->block;
    }
}
