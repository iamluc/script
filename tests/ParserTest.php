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
EOS
);

        $this->assertDumpEquals(<<<EOD
Iamluc\Script\Node\BlockNode {
  -nodes: array:2 [
    0 => Iamluc\Script\Node\AssignNode {
      -var: "hello"
      -value: Iamluc\Script\Node\ScalarNode {
        -value: "Salut"
      }
      -local: false
    }
    1 => Iamluc\Script\Node\AssignNode {
      -var: "world"
      -value: Iamluc\Script\Node\ScalarNode {
        -value: "le monde !"
      }
      -local: false
    }
  ]
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
            -var: "res"
            -value: Iamluc\Script\Node\ScalarNode {
              -value: "OK"
            }
            -local: false
          }
        ]
      }
      -else: Iamluc\Script\Node\BlockNode {
        -nodes: array:1 [
          0 => Iamluc\Script\Node\AssignNode {
            -var: "res"
            -value: Iamluc\Script\Node\ScalarNode {
              -value: "KO"
            }
            -local: false
          }
        ]
      }
    }
  ]
}
EOD
            , $node
        );
    }
}
