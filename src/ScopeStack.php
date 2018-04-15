<?php

namespace Iamluc\Script;

class ScopeStack
{
    /**
     * @var Scope[]
     */
    private $stack = [];
    private $index = -1;
    private $first;

    public function __construct(array $scopes = [])
    {
        foreach ($scopes as $scope) {
            $this->push($scope);
        }

        $this->first = $this->index;
    }

    public function push(Scope $scope = null)
    {
        if (null == $scope) {
            $scope = new Scope();
        }

        $this->stack[++$this->index] = $scope;
    }

    public function pop()
    {
        if ($this->index < 0) {
            throw new \LogicException('There is no scope to pop in stack.');
        }

        unset($this->stack[$this->index--]);
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

        return $this->first()->setVariable($name, $value);
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

    public function getVariables($withFunctions = false)
    {
        return $this->current()->getVariables($withFunctions);
    }

    public function getFunction($name): Node\FunctionDefinition\FunctionDefinitionInterface
    {
        $func = $this->getVariable($name);

        if (!$func instanceof Node\FunctionDefinition\FunctionDefinitionInterface) {
            throw new \LogicException(sprintf('Function "%s" is not defined.', $name));
        }

        return $func;
    }

    protected function first(): Scope
    {
        if (!isset($this->stack[$this->first])) {
            throw new \LogicException('There is no writable scope in stack.');
        }

        return $this->stack[1];
    }

    protected function current(): Scope
    {
        if ($this->index < 1) {
            throw new \LogicException('There is no scope in stack.');
        }

        return $this->stack[$this->index];
    }
}
