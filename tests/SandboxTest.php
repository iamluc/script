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
    public function testExpression($script, $expected, $autoReturn = true)
    {
        $parser = new Parser();
        $sandbox = new Sandbox();

        $script = $autoReturn ? 'return '.$script : $script;
        $result = $sandbox->eval($parser->parse($script));

        $this->assertEquals($expected, $result);
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

        yield ['4*3 == 4+8', true];
        yield ['4*3 ~= 4+8', false];
        yield ['1 > 2', false];
        yield ['1 >= 2', false];
        yield ['2 >= 2', true];
        yield ['1 < 2', true];
        yield ['1 <= 2', true];
        yield ['2 <= 2', true];
        yield ['3 <= 2', false];

        yield ['true and true', true];
        yield ['true and false', false];
        yield ['false and false', false];
        yield ['true or true', true];
        yield ['true or false', true];
        yield ['false or false', false];

        yield [
            <<<EOS
mult = 10
return 5 - 3 * mult +15 + 5
EOS
            , -5, false
        ];

        yield [
            <<<EOS
a = -2
b = -3
return a * b
EOS
            , 6, false
        ];

        yield [
            <<<EOS
return 2

return 3
EOS
            , 2, false
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

        $this->assertEquals($expected, $sandbox->getGlobals());
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
    public function testIf($script, $expectedGlobals, $expectedResult = null)
    {
        $parser = new Parser();
        $sandbox = new Sandbox();

        $block = $parser->parse($script);
        $result = $sandbox->eval($block);

        $this->assertEquals($expectedGlobals, $sandbox->getGlobals());
        $this->assertEquals($expectedResult, $result);
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
val = 1
if 1 == 2 then
    val = 2
    zz = 8
end

EOS
            , [
                'val' => 1,
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

return "Done"
EOS
            , [
                'val' => 8,
                'res' => 'OK',
            ],
            'Done'
        ];

        yield [
            <<<EOS
if false then
    val = 1
    return val+10
else
    val = 4
    return 3 + val
end
EOS
            , [
                'val' => 4,
            ],
            7
        ];

        yield ['if 1 > 2 then return true else return false end', [], false];
        yield ['if 1 >= 2 then return true else return false end', [], false];
        yield ['if 1 < 2 then return true else return false end', [], true];
        yield ['if 1 <= 2 then return true else return false end', [], true];

        yield ['if 2 + 4 <= 5 then return true else return false end', [], false];
        yield ['if 2 + 3 <= 5 then return true else return false end', [], true];
        yield ['if 2 + 2 <= 5 then return true else return false end', [], true];
    }

    /**
     * @dataProvider provideWhile
     */
    public function testWhile($script, $expectedGlobals, $expectedResult = null)
    {
        $parser = new Parser();
        $sandbox = new Sandbox();

        $block = $parser->parse($script);
        $result = $sandbox->eval($block);

        $this->assertEquals($expectedGlobals, $sandbox->getGlobals());
        $this->assertEquals($expectedResult, $result);
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

        yield [
            <<<EOS
a = 0
cpt = 2
while a ~= 3 do
    cpt = cpt * cpt
    a = a + 1
    
    return "FIN"
end

return "WHAT???"
EOS
            , [
                'a' => 1,
                'cpt' => 4,
            ],
            'FIN'
        ];

        yield [
            <<<EOS
a = 0
cpt = 2
while a ~= 3 do
    cpt = cpt * cpt
    a = a + 1

    if a == 2 then
        break
    end
end

return "THE END"
EOS
            , [
                'a' => 2,
                'cpt' => 16,
            ],
            'THE END'
        ];

        yield ['while a < 3 do a = a + 1 end return a *2', ['a' => 3], 6];
        yield ['while a <= 3 do a = a + 1 end return a *2', ['a' => 4], 8];
        yield ['a = 3 while a >= 0 do a = a - 1 end return a *2', ['a' => -1], -2];
    }

    /**
     * @dataProvider provideFunction
     */
    public function testFunction($script, $expectedGlobals, $expectedResult = null)
    {
        $parser = new Parser();
        $sandbox = new Sandbox();

        $block = $parser->parse($script);
        $result = $sandbox->eval($block);

        $this->assertEquals($expectedGlobals, $sandbox->getGlobals());
        $this->assertEquals($expectedResult, $result);
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

        yield [
            <<<EOS
function test()
    return 8
end

res = 12 + test()
EOS
            , [
                'res' => 20,
            ]
        ];

        yield [
            <<<EOS
a = 1
function test() a = 2 end
return true or test()
EOS
            , [
                'a' => 1,
            ],
            true
        ];

        yield [
            <<<EOS
a = 1
function test() a = 2 end
return true and test()
EOS
            , [
                'a' => 2,
            ],
            false
        ];

        yield [
            <<<EOS
a = 1
function test() a = 2 return true end
return true and test()
EOS
            , [
                'a' => 2,
            ],
            true
        ];

        yield [
            <<<EOS
a = 1
function test() a = 2 end
return false or test()
EOS
            , [
                'a' => 2,
            ],
            false
        ];

        yield [
            <<<EOS
a = 1
function test() a = 2 end
return false and test()
EOS
            , [
                'a' => 1,
            ],
            false
        ];

        yield [
            <<<EOS
andy = 1
function test() andy = 2 return false end
return true and test()
EOS
            , [
                'andy' => 2,
            ],
            false
        ];
    }
}
