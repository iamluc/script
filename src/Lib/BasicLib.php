<?php

namespace Iamluc\Script\Lib;

use Iamluc\Script\Node\FunctionDefinition\PhpFunctionNode;
use Iamluc\Script\Output;
use Iamluc\Script\Sandbox;
use Iamluc\Script\Scope;
use Iamluc\Script\Table;

class BasicLib implements LibInterface
{
    public function register(Scope $scope, Output $output)
    {
        $scope->setMulti([
            '_VERSION' => 'Lua 5.3',

            'print' => new PhpFunctionNode(function (...$str) use ($output) {
                $output->write(implode("\t", $str));
                $output->write("\n");
            }),

            'type' => new PhpFunctionNode(function ($subject) {
                return Sandbox::getType($subject);
            }),

            'pairs' => new PhpFunctionNode(function (Table $table) {
                foreach ($table->all() as $index => $value) { // FIXME
                    yield $index => $value;
                }
            }),

            'ipairs' => new PhpFunctionNode(function (Table $table) { // FIXME
                foreach ($table->all() as $index => $value) {
                    yield $index => $value;
                }
            }),
        ]);
    }
}
