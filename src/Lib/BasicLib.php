<?php

namespace Iamluc\Script\Lib;

use Iamluc\Script\Node\FunctionDefinition\PhpFunctionNode;
use Iamluc\Script\Output;
use Iamluc\Script\Scope;

class BasicLib implements LibInterface
{
    public function register(Scope $scope, Output $output)
    {
        $scope->setVariables([
            '_VERSION' => 'Lua 5.3',

            'print' => new PhpFunctionNode(function (...$str) use ($output) {
                $output->write(implode("\t", $str));
                $output->write("\n");
            }),
        ]);
    }
}
