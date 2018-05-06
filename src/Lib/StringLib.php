<?php

namespace Iamluc\Script\Lib;

use Iamluc\Script\Node\FunctionDefinition\PhpFunctionNode;
use Iamluc\Script\Output;
use Iamluc\Script\Scope;
use Iamluc\Script\Table;

class StringLib implements LibInterface
{
    public function register(Scope $scope, Output $output)
    {
        $scope->setMulti([
            'string' => new Table([
                'format' => new PhpFunctionNode(function ($str, ...$vars) {
                    return sprintf($str, ...$vars);
                }),
            ])
        ]);
    }
}
