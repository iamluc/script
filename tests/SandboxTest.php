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
    /**
     * @dataProvider provideExpression
     */
    public function testExpression($script, $expected)
    {
        $parser = new Parser();
        $sandbox = new Sandbox();
        $sandbox->eval($parser->parse('res = '.$script));

        $this->assertEquals($expected, $sandbox->getVariables()['res']);
    }

    public function provideExpression()
    {
        yield ['123', 123];
        yield ['2 + 8 -1', 9];
        yield ['4 * 5', 20];
        yield ['10 + 2 * 2 * 3 / 4', 13];
        yield ['4*5 +2/2 -5', 16];
        yield ['-2 * 10 + 5', -15];
        yield ['+10 - 3*4', -2];
        yield ['(+10 - 3)*4', 28];
        yield ['10* ((10*5) - 1)', 490];
        yield ['5 / (4+1) * 6 + 3 * 2 * 2', 18];
        yield ['-5 - (-3-1)', -1];

        yield [
            <<<EOS
mult = 10
res = 5 - 3 * mult +15 + 5
EOS
            , -5,
        ];

        yield [
            <<<EOS
a = -2
b = -3
res = a * b
EOS
            , 6
        ];
    }

    /**
     * @dataProvider provideAssign
     */
    public function testAssign($script, $expected)
    {
        $parser = new Parser();
        $sandbox = new Sandbox();
        $sandbox->eval($parser->parse($script));

        $this->assertEquals($expected, $sandbox->getVariables());
    }

    public function provideAssign()
    {
        yield [
            <<<EOS
hello = "Salut"
world = "le monde !"
EOS
            , [
                'hello' => 'Salut',
                'world' => 'le monde !',
            ]
        ];

        yield [
            <<<EOS
abc = def = 456
EOS
            , [
                'abc' => 456,
                'def' => 456,
            ]
        ];

        yield [
            <<<EOS
abc = def = 456 + 4
abc = false
EOS
            , [
                'abc' => false,
                'def' => 460,
            ]
        ];
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
if 1 == 1 then
    abc = 123
    def = 456
    ghi = "hourra"
    jkl = true 
end

EOS
            , [
                'abc' => 123,
                'def' => 456,
                'ghi' => 'hourra',
                'jkl' => true,
            ]
        ];

        yield [
            <<<EOS
if 1 == 1 then
    testA = "OK"
else
    testA = "KO"
end

if 1 == 2 then 
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
if 1 == 2 then
    val = "A"
elseif 1 ~= 2 then
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

        yield [
            <<<EOS
if 2 + 3 == 5 then
    res = true
else
    res = false
end
EOS
            , [
                'res' => true,
            ]
        ];

        yield [
            <<<EOS
if 2 + 2 == 5 then
    res = "KO"
elseif 4-2 + 8 == 1 + 2-1 +3 +6-1 then 
    res = "OK"
else
    res = "WHAT?"
end
EOS
            , [
                'res' => 'OK',
            ]
        ];

        yield [
            <<<EOS
val = 8
if (2 + 4) / 2 == (val + 1) / 3 then
    res = "OK"
elseif false then 
    res = "KO"
else
    res = "WHAT?"
end
EOS
            , [
                'val' => 8,
                'res' => 'OK',
            ]
        ];
    }

    /**
     * @dataProvider provideWhile
     */
    public function testWhile($script, $expected)
    {
        $parser = new Parser();
        $sandbox = new Sandbox();
        $sandbox->eval($parser->parse($script));

        $this->assertEquals($expected, $sandbox->getVariables());
    }

    public function provideWhile()
    {
        yield [
            <<<EOS
a = 0
cpt = 2
while a ~= 3 do
    cpt = cpt * cpt
    a = a + 1
end
EOS
            , [
                'a' => 3,
                'cpt' => 256,
            ]
        ];
    }

    /**
     * @dataProvider provideFunction
     */
    public function testFunction($script, $expected)
    {
        $parser = new Parser();
        $sandbox = new Sandbox();
        $sandbox->eval($parser->parse($script));

        $this->assertEquals($expected, $sandbox->getVariables());
    }

    public function provideFunction()
    {
        yield [
            <<<EOS
res = 0

function test()
    res = "test func"
end

test()
EOS
            , [
                'res' => 'test func',
            ]
        ];
    }
}
