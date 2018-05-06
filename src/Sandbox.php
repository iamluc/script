<?php

namespace Iamluc\Script;

use Iamluc\Script\Exception\BreakException;
use Iamluc\Script\Exception\CodeFlowException;
use Iamluc\Script\Exception\GotoException;
use Iamluc\Script\Exception\ReturnException;
use Iamluc\Script\Node\ResolvedNode;

class Sandbox
{
    /** @var ScopeStack */
    private $scopeStack;
    /** @var Output */
    private $output;

    public function eval(Node\BlockNode $main)
    {
        $this->output = new Output();
        $this->scopeStack = new ScopeStack($this->createGlobalScope());

        try {
            $returnSet = $this->evaluateBlockNode($main, true);
        } catch (BreakException $e) {
            throw new \LogicException('"break" not catched. Sandbox has a bug!');
        } catch (ReturnException $e) {
            throw new \LogicException('"return" not catched. Sandbox has a bug!');
        } catch (GotoException $e) {
            throw new \LogicException('"goto" not catched. Sandbox has a bug!');
        }

        return $returnSet->first(); // FIXME: return an ExecutionResult with all results, output, globals ?
    }

    public function getGlobals(): ?Scope
    {
        return $this->scopeStack->root();
    }

    public function getOutput(): Output
    {
        return $this->output;
    }

    private function createGlobalScope(): Scope
    {
        $scope = new Scope();
        $scope->add($scope, '_G');

        (new Lib\BasicLib())->register($scope, $this->output);
        (new Lib\IoLib())->register($scope, $this->output);
        (new Lib\TableLib())->register($scope, $this->output);
        (new Lib\MathLib())->register($scope, $this->output);
        (new Lib\StringLib())->register($scope, $this->output);

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
        if ($node instanceof Node\ScalarNode || $node instanceof ResolvedNode) {
            return $node->getValue();
        }

        if ($node instanceof Node\VariableNode) {
            return $this->scopeStack->get($node->getVariable());
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

        if ($node instanceof Node\ForInNode) {
            return $this->evaluateForInNode($node);
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

    private function evaluateForInNode(Node\ForInNode $node)
    {
        $exprList = $node->getExpressionList();

        $expr = $exprList[0];

        if ($expr instanceof Node\VariableNode || $expr instanceof Node\TableIndexNode) {
            return $this->evaluateForInCustomIterator($node);
        }

        $iterator = $this->evaluateNode($expr);
        if (!is_iterable($iterator)) {
            throw new \LogicException('Result of expression in "for in" is not iterable.');
        }

        foreach ($iterator as $index => $value) {
            try {
                $vars = [$node->getIndexVariable() => $index];
                if (null !== $node->getValueVariable()) {
                    $vars[$node->getValueVariable()] = $value;
                }

                $this->evaluateBlockNode($node->getBlock(), false, new Scope($vars));
            } catch (BreakException $break) {
                return;
            }
        }
    }

    private function evaluateForInCustomIterator(Node\ForInNode $node)
    {
        $exprList = $node->getExpressionList();

        $func = $exprList[0];
        $state = $exprList[1] ?? new Node\ScalarNode(null);
        $key = $exprList[2] ?? new Node\ScalarNode(null);

        while (true) {
            $call = new Node\CallNode($func, [$state, $key]);
            $res = $this->evaluateCallNode($call, false);

            $nextKeyVal = $res->first();
            if (null === $nextKeyVal) {
                break;
            }

            $extra = $res->extra();
            $val = $extra[0] ?? null;

            $vars = [$node->getIndexVariable() => $nextKeyVal];
            if (null !== $node->getValueVariable()) {
                $vars[$node->getValueVariable()] = $val;
            }

            $this->evaluateBlockNode($node->getBlock(), false, new Scope($vars));

            $key = new Node\ResolvedNode($nextKeyVal);
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
                $table = $this->evaluateIndexNode($var, true);
                $index = $this->evaluateNode($var->getIndex());
                $table->add(array_shift($resolvedValues), $index);
            } else {
                throw new \LogicException(sprintf('Expected VariableNode or TableIndexNode, got "%s"', get_class($var)));
            }
        }

        $this->scopeStack->setMulti($assignments, $node->isLocal());
    }

    private function evaluateCallNode(Node\CallNode $call, $firstOnly = true)
    {
        $function = $this->evaluateNode($call->getFunction());
        if (!$function instanceof Node\FunctionDefinition\FunctionDefinitionInterface) {
            throw new \LogicException(sprintf('Attempt to call a %s value', self::getType($function)));
        }

        $resolvedValues = $this->evaluateExpressionList($call->getArguments());

        if ($function instanceof Node\FunctionDefinition\PhpFunctionNode) {
            $res = call_user_func($function->getCallable(), ...array_values($resolvedValues));

            return $firstOnly ? $res : new ReturnSet($res);
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

                return (is_bool($value) || null === $value) ? !$value : false;
        }

        throw new \LogicException(sprintf('Cannot evaluate unary node with operator "%s"', $node->getOperator()));
    }

    private function evaluateDoNode(Node\DoNode $node)
    {
        $this->evaluateBlockNode($node->getBlock());
    }

    private function evaluateIndexNode(Node\TableIndexNode $node, $returnTable = false)
    {
        $table = $this->evaluateNode($node->getTable());
        if (!$table instanceof Table) {
            $base = $node->getTable();
            $path = $base instanceof Node\VariableNode ? 'variable "'.$base->getVariable().'"' : 'field "'.$this->evaluateNode($base->getIndex()).'"';

            throw new \LogicException(sprintf('Attempt to index a %s value (%s)', self::getType($table), $path));
        }

        if ($returnTable) {
            return $table;
        }

        $index = $this->evaluateNode($node->getIndex());

        return $table->get($index);
    }

    private function assertNumbers($left, $right)
    {
        if (!is_numeric($left) || !is_numeric($right)) {
            throw new \LogicException(sprintf('Attempt to compare %s with %s', self::getType($right), self::getType($left)));
        }
    }

    public static function getType($subject): string
    {
        if ($subject instanceof Table) {
            return 'table';
        }

        if ($subject instanceof Node\FunctionDefinition\FunctionDefinitionInterface) {
            return 'function';
        }

        return strtr(gettype($subject), [
            'double' => 'number',
            'NULL' => 'nil',
        ]);
    }
}
