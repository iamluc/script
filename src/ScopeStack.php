<?php

namespace Iamluc\Script;

class ScopeStack
{
    /**
     * @var Scope[]
     */
    private $stack = [];
    private $index = -1;
    private $firstWritable;

    public function __construct(Scope $global = null, Scope $script = null)
    {
        if ($global) {
            $this->push($global);
        }

        $this->push($script ?: new Scope());

        $this->firstWritable = $this->index;
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

        for ($i = $this->index; $i > $this->firstWritable; $i--) {
            $scope = $this->stack[$i];
            if ($scope->has($name)) {
                $scope->add($value, $name);

                return;
            }
        }

        $this->first()->add($value, $name);
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

    public function all(): array
    {
        $vars = [];
        foreach ($this->current()->all() as $index => $value) {
            if ($value instanceof Table) {
                $value = $value->toArray();
            }

            $vars[$index] = $value;
        }

        return array_filter($vars, function ($val) {
            return !$val instanceof Node\FunctionDefinition\FunctionDefinitionInterface;
        });
    }

    public function getFunction($name): Node\FunctionDefinition\FunctionDefinitionInterface
    {
        $func = $this->get($name);

        if (!$func instanceof Node\FunctionDefinition\FunctionDefinitionInterface) {
            throw new \LogicException(sprintf('Function "%s" is not defined.', $name));
        }

        return $func;
    }

    protected function first(): Scope
    {
        if (!isset($this->stack[$this->firstWritable])) {
            throw new \LogicException('There is no writable scope in stack.');
        }

        return $this->stack[1];
    }

    protected function current(): Scope
    {
        if ($this->index < $this->firstWritable) {
            throw new \LogicException('There is no scope in stack.');
        }

        return $this->stack[$this->index];
    }
}
