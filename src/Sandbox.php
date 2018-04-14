<?php

namespace Iamluc\Script;

use Iamluc\Script\Exception\BreakException;
use Iamluc\Script\Exception\GotoException;
use Iamluc\Script\Exception\ReturnException;
use Iamluc\Script\Node\LabelNode;

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
        } catch (GotoException $e) {
            throw new \LogicException('"goto" not catched. Sandbox has a bug!');
        }
    }

    public function getGlobals(): array
    {
        return $this->scopeStack->getVariables();
    }

    /**
     * @throws BreakException
     * @throws ReturnException
     * @throws GotoException
     */
    private function evaluateBlockNode(Node\BlockNode $block, $catchReturn = false)
    {
        $this->scopeStack->push();

        $target = null;

blockstart:
        foreach ($block->getNodes() as $node) {
            if (null !== $target) {
                if (!$node instanceof LabelNode || $node->getName() !== $target) {
                    continue;
                }

                $target = null;
            }

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
            } catch (GotoException $goto) {
                if (!$block->hasLabel($goto->getTarget())) {
                    $this->scopeStack->pop();

                    throw $goto;
                }

                $target = $goto->getTarget();

                goto blockstart;
            }
        }

        if (null !== $target) {
            throw new \LogicException(sprintf('Unable to find label "%s" in the current block.', $target));
        }

        $this->scopeStack->pop();
    }

    /**
     * @throws BreakException
     * @throws ReturnException
     * @throws GotoException
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

        if ($node instanceof Node\UnaryNode) {
            return $this->evaluateUnaryNode($node);
        }

        if ($node instanceof Node\AssignNode) {
            return $this->evaluateAssignNode($node);
        }

        if ($node instanceof Node\BinaryNode) {
            return $this->evaluateBinaryNode($node);
        }

        if ($node instanceof Node\ConditionalNode) {
            return $this->evaluateConditionalNode($node);
        }

        if ($node instanceof Node\WhileNode) {
            return $this->evaluateWhileNode($node);
        }

        if ($node instanceof Node\CallNode) {
            return $this->evaluateCallNode($node);
        }

        if ($node instanceof Node\ReturnNode) {
            throw new ReturnException($this->evaluateNode($node->getValue()));
        }

        if ($node instanceof Node\BreakNode) {
            throw new BreakException();
        }

        if ($node instanceof Node\GotoNode) {
            throw new GotoException($node->getTarget());
        }

        if ($node instanceof Node\LabelNode || $node instanceof Node\NoOperationNode) {
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
        $value = $node->getValue();
        if (!$value instanceof Node\FunctionNode) {
            $value = $this->evaluateNode($node->getValue());
        }

        return $this->scopeStack->setVariable($node->getVariableName(), $value, $node->isLocal());
    }

    private function evaluateCallNode(Node\CallNode $node)
    {
        $function = $this->scopeStack->getFunction($node->getFunctionName());

        return $this->evaluateBlockNode($function->getBlock(), true);
    }

    private function evaluateBinaryNode(Node\BinaryNode $node)
    {
        switch ($node->getOperator()) {
            case '==':
                return $this->evaluateNode($node->getLeft()) === $this->evaluateNode($node->getRight());

            case '~=':
                return $this->evaluateNode($node->getLeft()) !== $this->evaluateNode($node->getRight());

            case '>':
                return $this->evaluateNode($node->getLeft()) > $this->evaluateNode($node->getRight());

            case '>=':
                return $this->evaluateNode($node->getLeft()) >= $this->evaluateNode($node->getRight());

            case '<':
                return $this->evaluateNode($node->getLeft()) < $this->evaluateNode($node->getRight());

            case '<=':
                return $this->evaluateNode($node->getLeft()) <= $this->evaluateNode($node->getRight());

            case 'and':
                return $this->evaluateNode($node->getLeft()) && $this->evaluateNode($node->getRight());

            case 'or':
                return $this->evaluateNode($node->getLeft()) || $this->evaluateNode($node->getRight());

            case '+':
                return $this->evaluateNode($node->getLeft()) + $this->evaluateNode($node->getRight());

            case '-':
                return $this->evaluateNode($node->getLeft()) - $this->evaluateNode($node->getRight());

            case '*':
                return $this->evaluateNode($node->getLeft()) * $this->evaluateNode($node->getRight());

            case '/':
                return $this->evaluateNode($node->getLeft()) / $this->evaluateNode($node->getRight());
        }

        throw new \LogicException(sprintf('Cannot evaluate binary node with operator "%s"', $node->getOperator()));
    }

    private function evaluateUnaryNode(Node\UnaryNode $node)
    {
        switch ($node->getOperator()) {
            case '-':
                return -$this->evaluateNode($node->getValue());
        }

        throw new \LogicException(sprintf('Cannot evaluate unary node with operator "%s"', $node->getOperator()));
    }
}
