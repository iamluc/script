<?php

namespace Test\Iamluc\Script;

use Iamluc\Script\Lexer;
use Iamluc\Script\Parser;
use Iamluc\Script\Token;
use PHPUnit\Framework\TestCase;
use Symfony\Component\VarDumper\Test\VarDumperTestTrait;

class ParserTest extends TestCase
{
    use VarDumperTestTrait;

    public function testParser()
    {
        $parser = new Parser();
        $node = $parser->parse(<<<EOS
hello = "Salut"
world = "le monde !"

mult1, mult2 = 123, "abc"
EOS
);

        $this->assertDumpEquals(<<<EOD
Iamluc\Script\Node\BlockNode {
  -nodes: array:3 [
    0 => Iamluc\Script\Node\AssignNode {
      -assignments: array:1 [
        "hello" => Iamluc\Script\Node\ScalarNode {
          -value: "Salut"
        }
      ]
      -local: false
    }
    1 => Iamluc\Script\Node\AssignNode {
      -assignments: array:1 [
        "world" => Iamluc\Script\Node\ScalarNode {
          -value: "le monde !"
        }
      ]
      -local: false
    }
    2 => Iamluc\Script\Node\AssignNode {
      -assignments: array:2 [
        "mult1" => Iamluc\Script\Node\ScalarNode {
          -value: 123.0
        }
        "mult2" => Iamluc\Script\Node\ScalarNode {
          -value: "abc"
        }
      ]
      -local: false
    }
  ]
  -labels: []
}
EOD
        , $node
);
    }

    public function testParserConditional()
    {
        $parser = new Parser();
        $node = $parser->parse(<<<EOS
if toto then
    res = "OK"
else
    res = "KO"
end
EOS
        );

        $this->assertDumpEquals(<<<EOD
Iamluc\Script\Node\BlockNode {
  -nodes: array:1 [
    0 => Iamluc\Script\Node\ConditionalNode {
      -condition: Iamluc\Script\Node\VariableNode {
        -variable: "toto"
      }
      -if: Iamluc\Script\Node\BlockNode {
        -nodes: array:1 [
          0 => Iamluc\Script\Node\AssignNode {
            -assignments: array:1 [
              "res" => Iamluc\Script\Node\ScalarNode {
                -value: "OK"
              }
            ]
            -local: false
          }
        ]
        -labels: []
      }
      -else: Iamluc\Script\Node\BlockNode {
        -nodes: array:1 [
          0 => Iamluc\Script\Node\AssignNode {
            -assignments: array:1 [
              "res" => Iamluc\Script\Node\ScalarNode {
                -value: "KO"
              }
            ]
            -local: false
          }
        ]
        -labels: []
      }
    }
  ]
  -labels: []
}
EOD
            , $node
        );
    }
}
