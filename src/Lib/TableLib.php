<?php

namespace Iamluc\Script\Lib;

use Iamluc\Script\Node\FunctionDefinition\PhpFunctionNode;
use Iamluc\Script\Output;
use Iamluc\Script\Scope;
use Iamluc\Script\Table;

class TableLib implements LibInterface
{
    public function register(Scope $scope, Output $output)
    {
        $scope->setMulti([
            'table' => new Table([
                'sort' => new PhpFunctionNode(function (Table $table, $comp = null) {
                    if (null !== $comp) {
                        throw new \LogicException('Argument "comp" of "table.sort" is not implemented yet.');
                    }

                    $table->sort(function ($a, $b) {
                        return $a <=> $b;
                    });
                }),
            ])
        ]);
    }
}
