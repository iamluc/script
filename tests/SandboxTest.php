<?php

namespace Test\Iamluc\Script;

use Iamluc\Script\Parser;
use Iamluc\Script\Sandbox;
use PHPUnit\Framework\TestCase;

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
        // Arithmetic
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
        yield ['4.38+0.12', 4.5];
        yield ['2^3', 8];
        yield ['2^3^2', 512];
        yield ['-3 ^ 2', -9];
        yield ['1 - 3 ^ 2', -8];
        yield ['1 ^ - 3 ^ - 2', 1];

        // Strings
        yield ["'single quote'", 'single quote'];
        yield ["'single quote'", 'single quote'];
        yield ['"double quote"', 'double quote'];
        yield ['"double\tquote"', "double\tquote"];
        yield ['[[double square brackets]]', 'double square brackets'];
        yield ['[[double\tsquare\nbrackets]]', 'double\tsquare\nbrackets'];
        yield ['[[first line
new line]]', "first line\nnew line"];
        yield ['[=[one [[two]] one]=]', 'one [[two]] one'];
        yield ['[=[one [ [==[ one]=]', 'one [ [==[ one'];

        // Comparison
        yield ['4*3 == 4+8', true];
        yield ['4*3 ~= 4+8', false];
        yield ['1 > 2', false];
        yield ['1 >= 2', false];
        yield ['2 >= 2', true];
        yield ['1 < 2', true];
        yield ['1 <= 2', true];
        yield ['2 <= 2', true];
        yield ['3 <= 2', false];

        // Logic
        yield ['true and true', true];
        yield ['true and false', false];
        yield ['false and false', false];
        yield ['true or true', true];
        yield ['true or false', true];
        yield ['false or false', false];
        yield ['a = true 
        return "a is " .. (a and "true!" or "false!")', 'a is true!', false];
        yield ['a = false 
        return "a is " .. (a and "true!" or "false!")', 'a is false!', false];
        yield ['1==1+1 or 2~=2*1 and true', false];
        yield ['1==1+1 or 2==2*1 and true', true];
        yield ['1==1+1 or 2==2*1 and false', false];

        yield ['not true', false];
        yield ['not false', true];
        yield ['not 0', false];
        yield ['not 12', false];
        yield ['not 12', false];

        // Concatenation
        yield ['"Hi".." my ".."friend"', 'Hi my friend'];
        yield ['12 .. " = twelve"', '12 = twelve'];
        yield ['(2*4) .." tests OK"', '8 tests OK'];
        yield ['[["double squared" ]] ..[[string]]', '"double squared" string'];

        // Table
        yield ['{2+2, "blabla"; toto = 2 > 1}', [
            1 => 4,
            2 => 'blabla',
            'toto' => true
        ]];

        yield [
            <<<EOS
function func() return "11", "22", 33 end
return {"a", "b", func(), "d"}
EOS
            , [
                1 => 'a',
                2 => 'b',
                3 => '11',
                4 => 'd',
            ], false
        ];

        yield [
            <<<EOS
function func() return "11", "22", 33 end
return {"a", "b", func()}
EOS
            , [
                1 => 'a',
                2 => 'b',
                3 => '11',
                4 => '22',
                5 => '33',
            ], false
        ];

        yield [
            <<<EOS
function func() return "11", "22", 33 end
return {"a", "b", first = func()}
EOS
            , [
                1 => 'a',
                2 => 'b',
                'first' => '11',
            ], false
        ];

        // With variables
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
john = {name = "John", age = 12}

return 'My name is '..john['nam'..'e']
EOS
            , "My name is John", false
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
     * @dataProvider provideInvalidExpression
     */
    public function testInvalidExpression($script, $exceptionMessage)
    {
        $parser = new Parser();
        $sandbox = new Sandbox();

        $this->expectExceptionMessageRegExp($exceptionMessage);

        $res = $parser->parse('return '.$script);
        $sandbox->eval($res);
    }

    public function provideInvalidExpression()
    {
        yield ['not 1 > 2', '/Attempt to compare number with boolean/'];
        yield ['zz["42"]', '/Attempt to index a nil value "zz"/'];
        yield ["'first line
new line'", '/Unable to tokenize the stream near/']; // FIXME: Should be "Unfinished string near ..."
        yield ['"first line
new line"', '/Unable to tokenize the stream near/']; // FIXME: Should be "Unfinished string near ..."
        yield ['[[one [[two]] one]]', '/Invalid token in statement: "name"/']; // FIXME: Should be something like "<eof> expected near 'one'"
    }

    /**
     * @dataProvider provideAssign
     */
    public function testAssign($script, $expectedGlobals, $expectedResult = null)
    {
        $parser = new Parser();
        $sandbox = new Sandbox();
        $result = $sandbox->eval($parser->parse($script));

        $this->assertEquals($expectedGlobals, $sandbox->getGlobals());
        $this->assertEquals($expectedResult, $result);
    }

    public function provideAssign()
    {
        yield ['val = true', ['val' => true]];
        yield ['val = TRUE', ['val' => null]];
        yield ['val = "double quoted"', ['val' => 'double quoted']];
        yield ["val = 'single quoted'", ['val' => 'single quoted']];
        yield ['val = "double \"!!\" \'quoted\'"', ['val' => 'double "!!" \'quoted\'']];
        yield ["val = 'single \"!!\" \\'quoted\\''", ['val' => 'single "!!" \'quoted\'']];

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
hello, world = "Salut", "le monde !"
EOS
            , [
                'hello' => 'Salut',
                'world' => 'le monde !',
            ]
        ];

        yield [
            <<<EOS
local hello, world = "Salut", "le monde !"

return hello.." (wait for it) "..world
EOS
            , [], // to be or not to be ?
            'Salut (wait for it) le monde !'
        ];

        yield [
            <<<EOS
x,y,z="x","y","z"

x, y, z = y, z, x
EOS
            , [
                'x' => 'y',
                'y' => 'z',
                'z' => 'x',
            ]
        ];

        yield [
            <<<EOS
x, y, missing = "x", "y"
EOS
            , [
                'x' => 'x',
                'y' => 'y',
                'missing' => null,
            ]
        ];

        yield [
            <<<EOS
x, y = "x", "y", "too", "much"
EOS
            , [
                'x' => 'x',
                'y' => 'y',
            ]
        ];

        yield [
            <<<EOS
function twice() return "first", "second" end
x, y, z = "x", "y", twice()
EOS
            , [
                'x' => 'x',
                'y' => 'y',
                'z' => 'first',
            ]
        ];

        yield [
            <<<EOS
function twice() return "first", "second" end
x, y, z = "x", twice()
EOS
            , [
                'x' => 'x',
                'y' => 'first',
                'z' => 'second',
            ]
        ];

        yield [
            <<<EOS
function twice() return "first", "second" end
x, y, z = "x", twice(), "z"
EOS
            , [
                'x' => 'x',
                'y' => 'first',
                'z' => 'z',
            ]
        ];

        yield [
            <<<EOS
function twice() return "first", "second" end
function proxy() return twice() end
x, y, z = "x", proxy()
EOS
            , [
                'x' => 'x',
                'y' => 'first',
                'z' => 'second',
            ]
        ];

        yield [
            <<<EOS
        function twice() return "first", "second" end
function proxy() return twice(), "end" end
x, y, z = "x", proxy()
EOS
            , [
                'x' => 'x',
                'y' => 'first',
                'z' => 'end',
            ]
        ];

        yield [
            <<<EOS
function twice() return "first", "second" end
function nop(a, b, c) return a, b, c end
a, b, c = nop(twice())
EOS
            , [
                'a' => 'first',
                'b' => 'second',
                'c' => null,
            ]
        ];

        yield [
            <<<EOS
function twice() return "first", "second" end
function nop(a, b, c) return a, b, c end
a, b, c = nop(twice(), 'deuz')
EOS
            , [
                'a' => 'first',
                'b' => 'deuz',
                'c' => null,
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

        yield ['a = 0 while a < 3 do a = a + 1 end return a *2', ['a' => 3], 6];
        yield ['a = 0 while a <= 3 do a = a + 1 end return a *2', ['a' => 4], 8];
        yield ['a = 3 while a >= 0 do a = a - 1 end return a *2', ['a' => -1], -2];
    }

    /**
     * @dataProvider provideFunction
     */
    public function testFunction($script, $expectedGlobals, $expectedResult = null, $expectedOutput = '')
    {
        $parser = new Parser();
        $sandbox = new Sandbox();

        $block = $parser->parse($script);
        $result = $sandbox->eval($block);

        $this->assertEquals($expectedResult, $result);
        $this->assertEquals($expectedGlobals, $sandbox->getGlobals());
        $this->assertEquals($expectedOutput, $sandbox->getOutput());
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

        yield [
            <<<EOS
test = function() aa = 22 return 18 end
return test()
EOS
            , [
                'aa' => 22,
            ],
            18
        ];

        yield [
            <<<EOS
divi = function (z) return z / 2 end
multi = function(a, b) return a * divi(b) end
return multi(3, 10)
EOS
            , [],
            15
        ];

        yield [
            <<<EOS
function getFactory()
    return function() print("Created !") end
end
factory = getFactory()
factory()
factory()
EOS
            , [],
            null,
            'Created !
Created !
',
        ];

        yield [
            <<<EOS
function getFactory()
    return function() print("Created !") end
           , function() print("V2 !") end
end
factory, v2 = getFactory()
factory()
v2()
factory()
v2()
EOS
            , [],
            null,
            'Created !
V2 !
Created !
V2 !
',
        ];
    }

    public function testVariableScope()
    {
        $parser = new Parser();
        $sandbox = new Sandbox();

        # From https://www.lua.org/manual/5.1/manual.html#2.6
        $script = <<<EOS
x = 10                -- global variable
do                    -- new block
  local x = x         -- new 'x', with value 10
  print(x)            --> 10
  x = x+1
  do                  -- another block
    local x = x+1     -- another 'x'
    print(x)          --> 12
  end
  print(x)            --> 11
end
print(x)              --> 10  (the global one)
EOS;

        $block = $parser->parse($script);
        $result = $sandbox->eval($block);

        $this->assertEquals([
            'x' => 10,
        ], $sandbox->getGlobals());
        $this->assertEquals(null, $result);
        $this->assertEquals('10
12
11
10
'
            , $sandbox->getOutput()
        );
    }

    public function testFunctionScope()
    {
        $parser = new Parser();
        $sandbox = new Sandbox();

        $script = <<<EOS
function set()
    return "global"
end

closure = function()
    return "closure global"
end

set_1 = set()
closure_1 = closure()

do
    local function set()
        return "local"
    end

    local closure = function()
        return "closure local"
    end
    
    set_2 = set()
    closure_2 = closure()
end

set_3 = set()
closure_3 = closure()
EOS;

        $block = $parser->parse($script);
        $result = $sandbox->eval($block);

        $this->assertEquals([
            'set_1' => 'global',
            'set_2' => 'local',
            'set_3' => 'global',
            'closure_1' => 'closure global',
            'closure_2' => 'closure local',
            'closure_3' => 'closure global',
        ], $sandbox->getGlobals());
        $this->assertEquals(null, $result);
    }

    public function testGoto()
    {
        $parser = new Parser();
        $sandbox = new Sandbox();

        $script = <<<EOS
x = 0
::start::
x = x + 1

if x < 5 then
    goto start
end

x = x + 100
EOS;

        $block = $parser->parse($script);
        $result = $sandbox->eval($block);

        $this->assertEquals([
            'x' => 105,
        ], $sandbox->getGlobals());
        $this->assertEquals(null, $result);
    }

    public function testRepeatUntil()
    {
        $parser = new Parser();
        $sandbox = new Sandbox();

        $script = <<<EOS
x = 0
repeat
    x = x + 2
until x < 3

toto = "cool"
EOS;

        $block = $parser->parse($script);
        $result = $sandbox->eval($block);

        $this->assertEquals([
            'x' => 4,
            'toto' => 'cool',
        ], $sandbox->getGlobals());
        $this->assertEquals(null, $result);
    }

    public function testRepeatUntilLocalVar()
    {
        $parser = new Parser();
        $sandbox = new Sandbox();

        $script = <<<EOS
x = 0
repeat
    local loc = x
    x = x + 2
until loc < 3

return "Ok"
EOS;

        $block = $parser->parse($script);
        $result = $sandbox->eval($block);

        $this->assertEquals([
            'x' => 6,
        ], $sandbox->getGlobals());
        $this->assertEquals('Ok', $result);
    }

    /**
     * @dataProvider provideFor
     */
    public function testFor($script, $expectedGlobals, $expectedResult = null)
    {
        $parser = new Parser();
        $sandbox = new Sandbox();

        $block = $parser->parse($script);
        $result = $sandbox->eval($block);

        $this->assertEquals($expectedGlobals, $sandbox->getGlobals());
        $this->assertEquals($expectedResult, $result);
    }

    public function provideFor()
    {
        yield [
            <<<EOS
res = 0
for v = 3, 5 do
    res = res + v
end
EOS
            , [
                'res' => 12,
            ],
        ];

        yield [
            <<<EOS
res = 0
for v = 5, 3 do
    res = res + v
end
EOS
            , [
                'res' => 0,
            ],
        ];

        yield [
            <<<EOS
res = 0
for v = 1, 3, -1 do
    res = res + v
end
EOS
            , [
                'res' => 0,
            ],
        ];

        yield [
            <<<EOS
res = 0
for v = 1, 3, 1 do
    res = res + v
end
EOS
            , [
                'res' => 6,
            ],
        ];

        yield [
            <<<EOS
res = 0
for v = 2*2-1, 6-1, 1+1 do
    res = res + v
end
EOS
            , [
                'res' => 8,
            ],
        ];

        yield [
            'for i=1,0,-0.25 do print(i) end',
            [],
            null,
            '1.0
0.75
0.5
0.25
0.0
',
        ];

//        yield [
//            <<<EOS
//function it()
//    return 12
//end
//
//res = 0
//for v in it() do
//    res = res + v
//end
//EOS
//            , [
//                'res' => 8,
//            ],
//        ];
    }

    /**
     * @dataProvider provideComments
     */
    public function testComments($script, $expectedGlobals, $expectedResult = null)
    {
        $parser = new Parser();
        $sandbox = new Sandbox();

        $block = $parser->parse($script);
        $result = $sandbox->eval($block);

        $this->assertEquals($expectedGlobals, $sandbox->getGlobals());
        $this->assertEquals($expectedResult, $result);
    }

    public function provideComments()
    {
        yield [
            <<<EOS
-- return 1
return 2
EOS
            , [], 2
        ];

        yield [
            <<<EOS
-- prepare return
return "OK" -- do the return
EOS
            , [], 'OK'
        ];

        yield [
            <<<EOS
return "-- this is not a comment" -- do the return
EOS
            , [], '-- this is not a comment'
        ];

        yield [
            <<<EOS
return "-- this is not a comment" -- do the return
EOS
            , [], '-- this is not a comment'
        ];

        yield [
            <<<EOS
--[[
    Single long comment block
--]]
return 'OK'
EOS
            , [], 'OK'
        ];

        yield [
            <<<EOS
--[===[
    Single long comment block
--]===]
return 'OK'
EOS
            , [], 'OK'
        ];

        yield [
            <<<EOS
--[===[
    You can write long comments with --[[ --]]
--]===]
return 'OK'
EOS
            , [], 'OK'
        ];
    }
}
