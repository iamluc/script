<?php

namespace Iamluc\Script\Lib;

use Iamluc\Script\Node\FunctionDefinition\PhpFunctionNode;
use Iamluc\Script\Output;
use Iamluc\Script\Scope;
use Iamluc\Script\Table;

class MathLib implements LibInterface
{
    public function register(Scope $scope, Output $output)
    {
        $scope->setMulti([
            'math' => new Table([
                'abs' => new PhpFunctionNode(function ($value) {
                    return abs($value);
                }),
            ])
        ]);
    }
}
