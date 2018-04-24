<?php

namespace Iamluc\Script;

use Iamluc\Script\Exception\BreakException;
use Iamluc\Script\Exception\CodeFlowException;
use Iamluc\Script\Exception\GotoException;
use Iamluc\Script\Exception\ReturnException;

class Sandbox
{
    /** @var ScopeStack */
    private $scopeStack;
    /** @var Output */
    private $output;

    public function eval(Node\BlockNode $main)
    {
        $this->output = new Output();
        $this->scopeStack = new ScopeStack([
            $this->createLibrariesScope(),
        ]);

        try {
            $returnSet = $this->evaluateBlockNode($main, true);
        } catch (BreakException $e) {
            throw new \LogicException('"break" not catched. Sandbox has a bug!');
        } catch (ReturnException $e) {
            throw new \LogicException('"return" not catched. Sandbox has a bug!');
        } catch (GotoException $e) {
            throw new \LogicException('"goto" not catched. Sandbox has a bug!');
        }

        $first = $returnSet->first(); // FIXME: return an ExecutionResult with all results, output, script scope ?

        return $first instanceof Table ? $first->toArray() : $first;
    }

    public function getGlobals(): array
    {
        return $this->scopeStack->getVariables();
    }

    public function getOutput(): Output
    {
        return $this->output;
    }

    private function createLibrariesScope(): Scope
    {
        $scope = new Scope();

        (new Lib\BasicLib())->register($scope, $this->output);
        (new Lib\IoLib())->register($scope, $this->output);

        return $scope;
    }

    /**
     * @throws BreakException
     * @throws ReturnException
     * @throws GotoException
     */
    private function evaluateBlockNode(Node\BlockNode $block, $catchReturn = false, $scope = true)
    {
        if (true === $scope) {
            $scope = new Scope();
        }

        $scope and $this->scopeStack->push($scope);

        $target = null;

blockstart:
        foreach ($block->getNodes() as $node) {
            if (null !== $target) {
                if (!$node instanceof Node\LabelNode || $node->getName() !== $target) {
                    continue;
                }

                $target = null;
            }

            try {
                $this->evaluateNode($node);
            } catch (ReturnException $return) {
                $scope and $this->scopeStack->pop();

                if ($catchReturn) {
                    return $return->getReturnSet();
                }

                throw $return;
            } catch (BreakException $break) {
                $scope and $this->scopeStack->pop();

                throw $break;
            } catch (GotoException $goto) {
                if (!$block->hasLabel($goto->getTarget())) {
                    $scope and $this->scopeStack->pop();

                    throw $goto;
                }

                $target = $goto->getTarget();

                goto blockstart;
            }
        }

        if (null !== $target) {
            throw new \LogicException(sprintf('Unable to find label "%s" in the current block.', $target));
        }

        $scope and $this->scopeStack->pop();

        return new ReturnSet();
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

        if ($node instanceof Node\TableIndexNode) {
            return $this->evaluateIndexNode($node);
        }

        if ($node instanceof Node\DoNode) {
            return $this->evaluateDoNode($node);
        }

        if ($node instanceof Node\UnaryNode) {
            return $this->evaluateUnaryNode($node);
        }

        if ($node instanceof Node\AssignNode) {
            return $this->evaluateAssignNode($node);
        }

        if ($node instanceof Node\TableNode) {
            return $this->evaluateTableNode($node);
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

        if ($node instanceof Node\RepeatNode) {
            return $this->evaluateRepeatNode($node);
        }

        if ($node instanceof Node\ForNode) {
            return $this->evaluateForNode($node);
        }

        if ($node instanceof Node\ForeachNode) {
            return $this->evaluateForeachNode($node);
        }

        if ($node instanceof Node\CallNode) {
            return $this->evaluateCallNode($node);
        }

        if ($node instanceof Node\FunctionDefinition\ScriptFunctionNode) {
            return $node;
        }

        if ($node instanceof Node\ReturnNode) {
            $this->evaluateReturnNode($node);
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

    private function evaluateReturnNode(Node\ReturnNode $node)
    {
        $values = $this->evaluateExpressionList($node->getValues());

        throw new ReturnException(new ReturnSet($values));
    }

    private function evaluateConditionalNode(Node\ConditionalNode $node)
    {
        $value = $this->evaluateNode($node->getCondition());
        $toProcess = $value ? $node->getIf() : $node->getElse();

        if ($toProcess instanceof Node\BlockNode) {
            $this->evaluateBlockNode($toProcess);

            return;
        }

        $this->evaluateNode($toProcess);
    }

    private function evaluateWhileNode(Node\WhileNode $node)
    {
        while ($this->evaluateNode($node->getCondition())) {
            try {
                $this->evaluateBlockNode($node->getBlock());
            } catch (BreakException $break) {
                return;
            }
        }
    }

    private function evaluateRepeatNode(Node\RepeatNode $node)
    {
        $first = true;
        do {
            try {
                if (!$first) {
                    $this->scopeStack->pop();
                }
                $first = false;

                $this->scopeStack->push();

                // We manage the scope here as the condition can use local variables
                $this->evaluateBlockNode($node->getBlock(), false, false);
            } catch (CodeFlowException $flowException) {
                $this->scopeStack->pop();

                if ($flowException instanceof BreakException) {
                    return;
                }
            }
        } while ($this->evaluateNode($node->getCondition()));

        $this->scopeStack->pop();
    }

    private function evaluateForNode(Node\ForNode $node)
    {
        $initial = $this->evaluateNode($node->getInitial());
        $limit = $this->evaluateNode($node->getLimit());
        $step = $this->evaluateNode($node->getStep());

        if (!is_numeric($initial) || !is_numeric($limit) || !is_numeric($step)) {
            throw new \LogicException('Numerical "for" loop expects numeric values for "initial", "limit" and "step".');
        }

        if ($initial < $limit) {
            $cond = function ($i, $limit) { return $i <= $limit; };
            if ($step <= 0) {
                return;
            }
        } else {
            $cond = function ($i, $limit) { return $i >= $limit; };
            if ($step >= 0) {
                return;
            }
        }

        for ($i = $initial; $cond($i, $limit); $i += $step) {
            try {
                $this->evaluateBlockNode($node->getBlock(), false, new Scope([$node->getVariable() => $i]));
            } catch (BreakException $break) {
                return;
            }
        }
    }

    // FIXME: not tested
    private function evaluateForeachNode(Node\ForeachNode $node)
    {
        $iterator = $this->evaluateNode($node->getExpression());
        if (!is_iterable($iterator)) {
            throw new \LogicException('Result of expression in "for in" is not iterable.');
        }

        $values = is_array($iterator) ? $iterator : iterator_to_array($iterator);
        foreach ($values as $value) {
            try {
                $this->evaluateBlockNode($node->getBlock(), false, new Scope([$node->getVariable() => $value]));
            } catch (BreakException $break) {
                return;
            }
        }
    }

    /**
     * @see https://www.lua.org/manual/5.3/manual.html#3.3.3
     */
    private function evaluateAssignNode(Node\AssignNode $node)
    {
        $resolvedValues = $this->evaluateExpressionList(array_filter($node->getValues()));

        $assignments = [];
        foreach ($node->getVariables() as $var) {
            if ($var instanceof Node\VariableNode) { // Simple variable
                $assignments[$var->getVariable()] = array_shift($resolvedValues);
            } elseif ($var instanceof Node\TableIndexNode) {
                $table = $this->resolveTableAssign($var);
                $index = $this->evaluateNode($var->getIndex());
                $table->add(array_shift($resolvedValues), $index);
            } else {
                throw new \LogicException(sprintf('Expected VariableNode or TableIndexNode, got "%s"', get_class($var)));
            }
        }

        $this->scopeStack->setVariables($assignments, $node->isLocal());
    }

    private function resolveTableAssign(Node\TableIndexNode $index)
    {
        $var = $index->getTable();
        if ($var instanceof Node\TableIndexNode) {
            $var = $this->resolveTableAssign($var);
        }

        return $this->scopeStack->getVariable($var->getVariable());
    }

    private function evaluateCallNode(Node\CallNode $call, $firstOnly = true)
    {
        $function = $this->evaluateNode($call->getFunction());
        $resolvedValues = $this->evaluateExpressionList($call->getArguments());

        if ($function instanceof Node\FunctionDefinition\PhpFunctionNode) {
            $res = call_user_func($function->getCallable(), ...array_values($resolvedValues));

            return new ReturnSet($res);
        }

        $args = [];
        foreach ($function->getArguments() as $i => $name) {
            $args[$name] = array_shift($resolvedValues);
        }

        $set = $this->evaluateBlockNode($function->getBlock(), true, new Scope($args));
        if ($firstOnly) {
            return $set->first();
        }

        return $set;
    }

    private function evaluateExpressionList(array $expressions): array
    {
        $values = [];
        $extra = [];
        foreach ($expressions as $expr) {
            $extra = [];
            if ($expr instanceof Node\CallNode) { // We must keep all results of the call
                $returnSet = $this->evaluateCallNode($expr, false);
                $extra = $returnSet->extra();

                $values[] = $returnSet->first();
            } else {
                $values[] = $this->evaluateNode($expr);
            }
        }

        if ($extra) {
            $values = array_merge($values, $extra);
        }

        return $values;
    }

    private function evaluateTableNode(Node\TableNode $node)
    {
        $table = new Table();
        $extra = [];
        foreach ($node->getFields() as $field) {
            $extra = [];
            if ($field instanceof Node\AssignNode) {
                $values = $field->getValues();
                foreach ($field->getVariables() as $name) {
                    $name = $name->getVariable();
                    $value = $this->evaluateNode(array_shift($values));

                    $table->add($value, $name);
                }
            } elseif ($field instanceof Node\CallNode) {
                $returnSet = $this->evaluateCallNode($field, false);
                $extra = $returnSet->extra();

                $table->add($returnSet->first());
            } else {
                $table->add($this->evaluateNode($field));
            }
        }

        if ($extra) {
            foreach ($extra as $value) {
                $table->add($value);
            }
        }

        return $table;
    }

    private function evaluateBinaryNode(Node\BinaryNode $node)
    {
        $left = $this->evaluateNode($node->getLeft());

        switch ($node->getOperator()) {
            case 'and':
                return $left ? $this->evaluateNode($node->getRight()) : $left;

            case 'or':
                return $left ?: $this->evaluateNode($node->getRight());
        }

        $right = $this->evaluateNode($node->getRight());

        switch ($node->getOperator()) {
            case '==':
                return $left === $right;

            case '~=':
                return $left !== $right;

            case '..':
                return $left . $right;
        }

        $this->assertNumbers($left, $right);
        switch ($node->getOperator()) {
            case '>':
                return $left > $right;

            case '>=':
                return $left >= $right;

            case '<':
                return $left < $right;

            case '<=':
                return $left <= $right;

            case '+':
                return $left + $right;

            case '-':
                return $left - $right;

            case '*':
                return $left * $right;

            case '/':
                return $left / $right;

            case '^':
                return $left ** $right;
        }

        throw new \LogicException(sprintf('Cannot evaluate binary node with operator "%s"', $node->getOperator()));
    }

    private function evaluateUnaryNode(Node\UnaryNode $node)
    {
        switch ($node->getOperator()) {
            case '-':
                return -$this->evaluateNode($node->getValue());

            case 'not':
                $value = $this->evaluateNode($node->getValue());

                return is_bool($value) ? !$value : false;
        }

        throw new \LogicException(sprintf('Cannot evaluate unary node with operator "%s"', $node->getOperator()));
    }

    private function evaluateDoNode(Node\DoNode $node)
    {
        $this->evaluateBlockNode($node->getBlock());
    }

    private function evaluateIndexNode(Node\TableIndexNode $node)
    {
        $table = $this->evaluateNode($node->getTable());
        if (!$table instanceof Table) {
            $base = $node->getTable();
            $path = $base instanceof Node\VariableNode ? 'variable "'.$base->getVariable().'"' : 'field "'.$this->evaluateNode($base->getIndex()).'"';

            throw new \LogicException(sprintf('Attempt to index a %s value (%s)', $this->getResolvedType($table), $path));
        }

        $index = $this->evaluateNode($node->getIndex());

        return $table->get($index);
    }

    private function assertNumbers($left, $right)
    {
        if (!is_numeric($left) || !is_numeric($right)) {
            throw new \LogicException(sprintf('Attempt to compare %s with %s', $this->getResolvedType($right), $this->getResolvedType($left)));
        }
    }

    private function getResolvedType($left): string
    {
        return strtr(gettype($left), [
            'double' => 'number',
            'NULL' => 'nil',
        ]);
    }
}
