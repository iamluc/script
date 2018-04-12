<?php

namespace Iamluc\Script;

use Iamluc\Script\Exception\ReturnException;

class Sandbox
{
    /** @var Scope */
    private $globalScope;

    public function eval(Node\BlockNode $main)
    {
        $this->globalScope = new Scope();

        return $this->evaluateBlockNode($main, false, true);
    }

    public function getGlobals(): array
    {
        return $this->globalScope->getVariables();
    }

    private function evaluateBlockNode(Node\BlockNode $block, $catchBreak = false, $catchReturn = false)
    {
        foreach ($block->getNodes() as $node) { // FIXME: create local scope
            try {
                $this->evaluateNode($node);
            } catch (ReturnException $return) {
                if ($catchReturn) {
                    return $return->getValue();
                }

                throw $return;
            }
        }
    }

    private function evaluateNode(Node\Node $node)
    {
        if ($node instanceof Node\ScalarNode) {
            return $node->getValue();
        }

        if ($node instanceof Node\VariableNode) {
            return $this->globalScope->getVariable($node->getVariable());
        }

        if ($node instanceof Node\BlockNode) {
            return $this->evaluateBlockNode($node);
        }

        if ($node instanceof Node\NegativeNode) {
            return -$this->evaluateNode($node->getValue());
        }

        if ($node instanceof Node\AssignNode) {
            return $this->evaluateAssignNode($node);
        }

        if ($node instanceof Node\ComparisonNode) {
            return $this->evaluateComparisonNode($node);
        }

        if ($node instanceof Node\ConditionalNode) {
            return $this->evaluateConditionalNode($node);
        }

        if ($node instanceof Node\WhileNode) {
            return $this->evaluateWhileNode($node);
        }

        if ($node instanceof Node\MathNode) {
            return $this->evaluateMathNode($node);
        }

        if ($node instanceof Node\FunctionNode) {
            return $this->evaluateFunctionNode($node);
        }

        if ($node instanceof Node\CallNode) {
            return $this->evaluateCallNode($node);
        }

        if ($node instanceof Node\ReturnNode) {
            throw new ReturnException($this->evaluateReturnNode($node));
        }

        throw new \LogicException(sprintf('Unable to evaluateNode node of type %s', get_class($node)));
    }

    private function evaluateConditionalNode(Node\ConditionalNode $node)
    {
        $value = $this->evaluateNode($node->getCondition());
        $toProcess = $value ? $node->getIf() : $node->getElse();

        return $this->evaluateNode($toProcess);
    }

    private function evaluateWhileNode(Node\WhileNode $node)
    {
        while ($value = $this->evaluateNode($node->getCondition())) {
            $this->evaluateBlockNode($node->getBlock(), true);
        }
    }

    private function evaluateAssignNode(Node\AssignNode $node)
    {
        return $this->globalScope->setVariable($node->getVariableName(), $this->evaluateNode($node->getValue()));
    }

    private function evaluateFunctionNode(Node\FunctionNode $node)
    {
        $this->globalScope->setFunction($node->getName(), $node);
    }

    private function evaluateCallNode(Node\CallNode $node)
    {
        $function = $this->globalScope->getFunction($node->getFunctionName());

        return $this->evaluateBlockNode($function->getBlock(), false, true);
    }

    private function evaluateReturnNode(Node\ReturnNode $node)
    {
        return $this->evaluateNode($node->getValue());
    }

    private function evaluateComparisonNode(Node\ComparisonNode $comparison)
    {
        switch ($comparison->getOperator()) {
            case '==':
                return $this->evaluateNode($comparison->getLeft()) === $this->evaluateNode($comparison->getRight());

            case '~=':
                return $this->evaluateNode($comparison->getLeft()) !== $this->evaluateNode($comparison->getRight());
        }

        throw new \LogicException(sprintf('Cannot evaluateNode comparison "%s"', $comparison->getOperator()));
    }

    private function evaluateMathNode(Node\MathNode $comparison)
    {
        switch ($comparison->getOperator()) {
            case '+':
                return $this->evaluateNode($comparison->getLeft()) + $this->evaluateNode($comparison->getRight());

            case '-':
                return $this->evaluateNode($comparison->getLeft()) - $this->evaluateNode($comparison->getRight());

            case '*':
                return $this->evaluateNode($comparison->getLeft()) * $this->evaluateNode($comparison->getRight());

            case '/':
                return $this->evaluateNode($comparison->getLeft()) / $this->evaluateNode($comparison->getRight());
        }

        throw new \LogicException(sprintf('Cannot evaluateNode math "%s"', $comparison->getOperator()));
    }
}
