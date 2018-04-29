<?php

namespace Iamluc\Script;

class ScopeStack
{
    /**
     * @var Scope[]
     */
    private $stack = [];
    private $index = -1;

    public function __construct(Scope $root = null)
    {
        if ($root) {
            $this->push($root);
        }
    }

    public function push(Scope $scope = null)
    {
        if (null === $scope) {
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

    public function setMulti(array $vars, $local = false)
    {
        foreach ($vars as $name => $value) {
            $this->set($name, $value, $local);
        }
    }

    public function set($name, $value, $local = false): void
    {
        if ($local) {
            $this->current()->add($value, $name);

            return;
        }

        for ($i = $this->index; $i >= 0; $i--) {
            $scope = $this->stack[$i];
            if ($scope->has($name)) {
                $scope->add($value, $name);

                return;
            }
        }

        $this->root()->add($value, $name);
    }

    public function get($name)
    {
        for ($i = $this->index; $i >= 0; $i--) {
            $scope = $this->stack[$i];
            if ($scope->has($name)) {
                return $scope->get($name);
            }
        }

        return null;
    }

    public function root(): Scope
    {
        if (!isset($this->stack[0])) {
            throw new \LogicException('There is no scope in stack.');
        }

        return $this->stack[0];
    }

    protected function current(): Scope
    {
        if ($this->index < 0) {
            throw new \LogicException('There is no scope in stack.');
        }

        return $this->stack[$this->index];
    }
}
