<?php

namespace Iamluc\Script;

use Iamluc\Script\Exception\BreakException;
use Iamluc\Script\Exception\ReturnException;

class Sandbox
{
    /** @var ScopeStack */
    private $scopeStack;

    public function eval(Node\BlockNode $main)
    {
        $this->scopeStack = new ScopeStack();

        try {
            return $this->evaluateBlockNode($main, true);
        } catch (BreakException $e) {
            throw new \LogicException('"break" not catched. Sandbox has a bug!');
        } catch (ReturnException $e) {
            throw new \LogicException('"return" not catched. Sandbox has a bug!');
        }
    }

    public function getGlobals(): array
    {
        return $this->scopeStack->getVariables();
    }

    /**
     * @throws BreakException
     * @throws ReturnException
     */
    private function evaluateBlockNode(Node\BlockNode $block, $catchReturn = false)
    {
        $this->scopeStack->push();

        foreach ($block->getNodes() as $node) {
            try {
                $this->evaluateNode($node);
            } catch (ReturnException $return) {
                $this->scopeStack->pop();

                if ($catchReturn) {
                    return $return->getValue();
                }

                throw $return;
            } catch (BreakException $break) {
                $this->scopeStack->pop();

                throw $break;
            }
        }

        $this->scopeStack->pop();
    }

    /**
     * @throws BreakException
     * @throws ReturnException
     */
    private function evaluateNode(Node\Node $node)
    {
        if ($node instanceof Node\ScalarNode) {
            return $node->getValue();
        }

        if ($node instanceof Node\VariableNode) {
            return $this->scopeStack->getVariable($node->getVariable());
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

        if ($node instanceof Node\LogicalNode) {
            return $this->evaluateLogicalNode($node);
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

        if ($node instanceof Node\BreakNode) {
            throw new BreakException();
        }

        if ($node instanceof Node\NoOperationNode) {
            return;
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
            try {
                $this->evaluateBlockNode($node->getBlock());
            } catch (BreakException $break) {
                return;
            }
        }
    }

    private function evaluateAssignNode(Node\AssignNode $node)
    {
        return $this->scopeStack->setVariable($node->getVariableName(), $this->evaluateNode($node->getValue()), $node->isLocal());
    }

    private function evaluateFunctionNode(Node\FunctionNode $node)
    {
        $this->scopeStack->setFunction($node->getName(), $node, $node->isLocal());
    }

    private function evaluateCallNode(Node\CallNode $node)
    {
        $function = $this->scopeStack->getFunction($node->getFunctionName());

        return $this->evaluateBlockNode($function->getBlock(), true);
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

            case '>':
                return $this->evaluateNode($comparison->getLeft()) > $this->evaluateNode($comparison->getRight());

            case '>=':
                return $this->evaluateNode($comparison->getLeft()) >= $this->evaluateNode($comparison->getRight());

            case '<':
                return $this->evaluateNode($comparison->getLeft()) < $this->evaluateNode($comparison->getRight());

            case '<=':
                return $this->evaluateNode($comparison->getLeft()) <= $this->evaluateNode($comparison->getRight());
        }

        throw new \LogicException(sprintf('Cannot evaluate comparison node with operator "%s"', $comparison->getOperator()));
    }

    private function evaluateLogicalNode(Node\LogicalNode $logical)
    {
        switch ($logical->getOperator()) {
            case 'and':
                return $this->evaluateNode($logical->getLeft()) && $this->evaluateNode($logical->getRight());

            case 'or':
                return $this->evaluateNode($logical->getLeft()) || $this->evaluateNode($logical->getRight());
        }

        throw new \LogicException(sprintf('Cannot evaluate logical node with operator "%s"', $logical->getOperator()));
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

        throw new \LogicException(sprintf('Cannot evaluate math node with operator "%s"', $comparison->getOperator()));
    }
}
