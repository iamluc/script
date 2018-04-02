<?php

namespace Test\Iamluc\Script;

use Iamluc\Script\Lexer;
use Iamluc\Script\Parser;
use Iamluc\Script\Sandbox;
use Iamluc\Script\Token;
use PHPUnit\Framework\TestCase;
use Symfony\Component\VarDumper\Test\VarDumperTestTrait;

class SandboxTest extends TestCase
{
    public function testMultiAssign()
    {
        $parser = new Parser();
        $node = $parser->parse(<<<EOS
hello = "Salut"
world = "le monde !"
EOS
        );

        $sandbox = new Sandbox();
        $sandbox->eval($node);

        $this->assertEquals([
            'hello' => 'Salut',
            'world' => 'le monde !',
        ], $sandbox->getVariables());
    }

    public function testIfElse()
    {
        $parser = new Parser();
        $node = $parser->parse(<<<EOS
if true then
    testA = "OK"
else
    testA = "KO"
end

if false then 
testB = "OK"
else
testB = "KO"
end
EOS
        );

        $sandbox = new Sandbox();
        $sandbox->eval($node);

        $this->assertEquals([
            'testA' => 'OK',
            'testB' => 'KO',
        ], $sandbox->getVariables());
    }
}
