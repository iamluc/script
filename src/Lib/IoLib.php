<?php

namespace Iamluc\Script\Lib;

use Iamluc\Script\Node\FunctionDefinition\PhpFunctionNode;
use Iamluc\Script\Output;
use Iamluc\Script\Scope;
use Iamluc\Script\Table;

class IoLib implements LibInterface
{
    public function register(Scope $scope, Output $output)
    {
        $scope->setVariables([
            'io' => new Table([
                'write' => new PhpFunctionNode(function (...$str) use ($output) {
                    $output->write(implode('', $str));
                }),
            ])
        ]);
    }
}
