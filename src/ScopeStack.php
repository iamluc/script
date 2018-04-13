<?php

namespace Iamluc\Script;

class ScopeStack
{
    /**
     * @var Scope[]
     */
    private $stack = [];
    private $index = -1;

    public function __construct()
    {
        $this->push();
    }

    public function push()
    {
        $this->stack[++$this->index] = new Scope();
    }

    public function pop()
    {
        if ($this->index < 0) {
            throw new \LogicException('There is no scope to pop in stack.');
        }

        if (0 === $this->index) {
            throw new \LogicException('You cannot pop the root scope.');
        }

        unset($this->stack[$this->index--]);
    }

    public function root(): Scope
    {
        if ($this->index < 0) {
            throw new \LogicException('There is no scope in stack.');
        }

        return $this->stack[0];
    }

    public function current(): Scope
    {
        if ($this->index < 0) {
            throw new \LogicException('There is no scope in stack.');
        }

        return $this->stack[$this->index];
    }

    public function setVariable($name, $value, $local = false)
    {
        if ($local) {
            return $this->current()->setVariable($name, $value);
        }

        for ($i = $this->index; $i >= 0; $i--) {
            $scope = $this->stack[$i];
            if ($scope->hasVariable($name)) {
                return $scope->setVariable($name, $value);
            }
        }

        return $this->root()->setVariable($name, $value);
    }

    public function getVariable($name)
    {
        for ($i = $this->index; $i >= 0; $i--) {
            $scope = $this->stack[$i];
            if ($scope->hasVariable($name)) {
                return $scope->getVariable($name);
            }
        }

        return null;
    }

    public function getVariables($noFunctions = true): array
    {
        $variables = $this->current()->getVariables();
        if (false === $noFunctions) {
            return $variables;
        }

        return array_filter($variables, function ($val) {
            return !$val instanceof Node\FunctionNode;
        });
    }

    public function getFunction($name): Node\FunctionNode
    {
        $func = $this->getVariable($name);

        if (!$func instanceof Node\FunctionNode) {
            throw new \LogicException(sprintf('Function "%s" is not defined.', $name));
        }

        return $func;
    }
}
