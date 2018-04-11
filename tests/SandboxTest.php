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
        $sandbox->eval($parser->parse($script));

        $this->assertEquals($expected, $sandbox->getVariables());
    }

    public function provideExpression()
    {
        yield [
            <<<EOS
abc = 123
EOS
            , [
                'abc' => 123,
            ]
        ];

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
val = 2 + 8 -1
EOS
            , [
                'val' => 9,
            ]
        ];

        yield [
            <<<EOS
val = 4 * 5
EOS
            , [
                'val' => 20,
            ]
        ];

        yield [
            <<<EOS
val = 4*5 +2/2 -5
EOS
            , [
                'val' => 16,
            ]
        ];

        yield [
            <<<EOS
mult = 10
val = 5 - 3 * mult +15 + 5
EOS
            , [
                'val' => -5,
                'mult' => 10,
            ]
        ];

        yield [
            <<<EOS
a = -2
b = -3
val = a * b
EOS
            , [
                'a' => -2,
                'b' => -3,
                'val' => 6,
            ]
        ];

        yield [
            <<<EOS
val = -2 * 10 + 5
EOS
            , [
                'val' => -15,
            ]
        ];

        yield [
            <<<EOS
val = +10 - 3*4
EOS
            , [
                'val' => -2,
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
    }
}
