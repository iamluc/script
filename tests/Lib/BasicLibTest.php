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
    public function testSBasicLib($script, $expectedGlobals, $expectedResult = null, $expectedOutput = '')
    {
        $parser = new Parser();
        $sandbox = new Sandbox();

        $block = $parser->parse($script);
        $result = $sandbox->eval($block);

        $this->assertEquals($expectedResult, $result);
        $this->assertEquals($expectedGlobals, $sandbox->getVariables());
        $this->assertEquals($expectedOutput, $sandbox->getOutput());
    }

    public function provideBasicLib()
    {
        yield [
            <<<EOS
print("salut from ".._VERSION)
EOS
            ,
            [],
            null,
            "salut from Lua 5.3\n",
        ];
    }
}
