<?php

namespace Test\Iamluc\Script;

use Iamluc\Script\Parser;
use Iamluc\Script\Sandbox;
use PHPUnit\Framework\TestCase;

class BasicLibTest extends TestCase
{
    /**
     * @dataProvider provideBasicLib
     */
    public function testBasicLib($script, $expectedResult = null, $expectedOutput = '')
    {
        $parser = new Parser();
        $sandbox = new Sandbox();

        $block = $parser->parse($script);
        $result = $sandbox->eval($block);

        $this->assertEquals($expectedResult, $result);
        $this->assertEquals($expectedOutput, $sandbox->getOutput());
    }

    public function provideBasicLib()
    {
        yield [
            <<<EOS
print("salut from ".._VERSION)
EOS
            ,
            null,
            "salut from Lua 5.3\n",
        ];

        yield [
            <<<EOS
print(_G._VERSION)
print(_G._G._G["_G"]['_G']._VERSION)
_G['_G']._VERSION = 'Lua 99'
print(_G._VERSION)
EOS
            ,
            null,
            <<<EOD
Lua 5.3
Lua 5.3
Lua 99

EOD
,
        ];
    }
}
