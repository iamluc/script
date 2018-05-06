<?php

namespace Iamluc\Script\Extension;

use Iamluc\Script\Node\FunctionDefinition\FunctionDefinitionInterface;

class BlackfireExtension implements ExtensionInterface
{
    private $callStack;
    private $edges;

    public function __construct()
    {
        $this->callStack = ['main()'];
        $this->edges = [];
    }

    public function dispatch($eventName, $args)
    {
        switch ($eventName) {
            case 'call':
                $this->call($args[0], $args[1]);
                break;

            case 'variable':
                $this->variable($args[0]);
                break;

            case 'index':
                $this->index($args[0]);
                break;
        }
    }

    private function call($function, array $args)
    {
        //dump($function);
    }

    private function variable($name)
    {
        dump($name);
    }

    private function index($index)
    {
        dump($index);
    }
}
