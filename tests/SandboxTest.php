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

    /**
     * @dataProvider provideIf
     */
    public function testIf($script, $expected)
    {
        $parser = new Parser();
        $sandbox = new Sandbox();
        $sandbox->eval($parser->parse($script));

        $this->assertEquals($expected, $sandbox->getVariables());
    }

    public function provideIf()
    {
        yield [
            <<<EOS
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
            , [
                'testA' => 'OK',
                'testB' => 'KO',
            ]
        ];

        yield [
            <<<EOS
if false then
    val = "A"
elseif true then
    val = "B"
end
EOS
            , [
                'val' => 'B',
            ]
        ];

        yield [
            <<<EOS
if false then
    val = "A"
elseif false then
    val = "B"
else
    val = "C"
end
EOS
            , [
                'val' => 'C',
            ]
        ];

        yield [
            <<<EOS
if false then
    val = "A"
elseif true then
    val = "B"
else
    val = "C"
end
EOS
            , [
                'val' => 'B',
            ]
        ];

        yield [
            <<<EOS
if false then
    val = "A"
elseif true then
    if false then
        val = "B-1"
    else
        val = "B-2"
    end
else
    val = "C"
end
EOS
            , [
                'val' => 'B-2',
            ]
        ];
    }
}
