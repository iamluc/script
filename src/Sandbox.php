<?php

namespace Iamluc\Script;

class Sandbox
{
    private $variables = [];
    private $functions = [];

    public function eval(Node\BlockNode $main)
    {
        foreach ($main->getNodes() as $node) {
            $this->evaluateNode($node);
        }
    }

    public function getVariables(): array
    {
        return $this->variables;
    }

    private function evaluateNode(Node\Node $node)
    {
        if ($node instanceof Node\ScalarNode) {
            return $node->getValue();
        }

        if ($node instanceof Node\VariableNode) {
            if (array_key_exists($node->getVariable(), $this->variables)) {
                return $this->variables[$node->getVariable()];
            }

            throw new \LogicException(sprintf('Unknown variable "%s"', $this->getVariables()));
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

        throw new \LogicException(sprintf('Unable to evaluateNode node of type %s', get_class($node)));
    }

    private function evaluateBlockNode(Node\BlockNode $block)
    {
        foreach ($block->getNodes() as $node) {
            $this->evaluateNode($node);
        }
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
            $this->evaluateNode($node->getBlock());
        }
    }

    private function evaluateAssignNode(Node\AssignNode $node)
    {
        return $this->variables[$node->getVariableName()] = $this->evaluateNode($node->getValue());
    }

    private function evaluateFunctionNode(Node\FunctionNode $node)
    {
        $this->functions[$node->getName()] = $node;
    }

    private function evaluateCallNode(Node\CallNode $node)
    {
        if (!isset($this->functions[$node->getFunctionName()])) {
            throw new \LogicException(sprintf('Function "%s" is not defined.', $node->getFunctionName()));
        }

        $function = $this->functions[$node->getFunctionName()];

        return $this->evaluateNode($function->getBlock());
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
