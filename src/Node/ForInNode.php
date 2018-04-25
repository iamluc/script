<?php

namespace Iamluc\Script\Node;

class ForInNode extends Node
{
    private $indexVariable;
    private $expression;
    private $valueVariable;
    private $block;

    public function __construct(string $indexVariable, string $valueVariable = null, Node $expression, BlockNode $block)
    {
        $this->indexVariable = $indexVariable;
        $this->expression = $expression;
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

    public function getExpression(): Node
    {
        return $this->expression;
    }

    public function getBlock(): BlockNode
    {
        return $this->block;
    }
}
