<?php

namespace Test\Iamluc\Script;

use Iamluc\Script\Lexer;
use Iamluc\Script\Token;
use PHPUnit\Framework\TestCase;
use Symfony\Component\VarDumper\Test\VarDumperTestTrait;

class LexerTest extends TestCase
{
    use VarDumperTestTrait;

    /**
     * @dataProvider provideLexerTypes
     */
    public function testLexerTypes($stream, $type, $value, $line, $column)
    {
        $lexer = new Lexer($stream);
        $token = $lexer->next();

        $this->assertInstanceOf(Token::class, $token);
        $this->assertEquals($type, $token->getType());
        $this->assertEquals($value, $token->getValue());
        $this->assertEquals($line, $token->getLine());
        $this->assertEquals($column, $token->getColumn());
    }

    public function provideLexerTypes()
    {
        yield ['my_var', Token::T_NAME, 'my_var', 1, 1];
        yield ['"my simple string"', Token::T_STRING, 'my simple string', 1, 1];
        yield ['  =  ', Token::T_ASSIGN, '=', 1, 3];
        yield [' if ', Token::T_IF, 'if', 1, 2];
        yield ['then', Token::T_THEN, 'then', 1, 1];
        yield ['else ', Token::T_ELSE, 'else', 1, 1];
        yield ['end ', Token::T_END, 'end', 1, 1];
        yield ['', Token::T_EOF, null, 1, 1];
    }

    public function testLexerMultiLines()
    {
        $lexer = new Lexer(<<<EOS
hello = "Salut"
world = "le monde !"
EOS
        );

        $this->assertDumpEquals(<<<EOD
array:7 [
  0 => Iamluc\Script\Token {
    -type: "name"
    -value: "hello"
    -line: 1
    -column: 1
  }
  1 => Iamluc\Script\Token {
    -type: "assign"
    -value: "="
    -line: 1
    -column: 7
  }
  2 => Iamluc\Script\Token {
    -type: "string"
    -value: "Salut"
    -line: 1
    -column: 9
  }
  3 => Iamluc\Script\Token {
    -type: "name"
    -value: "world"
    -line: 2
    -column: 1
  }
  4 => Iamluc\Script\Token {
    -type: "assign"
    -value: "="
    -line: 2
    -column: 7
  }
  5 => Iamluc\Script\Token {
    -type: "string"
    -value: "le monde !"
    -line: 2
    -column: 9
  }
  6 => Iamluc\Script\Token {
    -type: "end of file"
    -value: null
    -line: 2
    -column: 21
  }
]
EOD
            , $lexer->all());
    }

    public function testLexerConditional()
    {
        $lexer = new Lexer(<<<EOS
if toto then
    res = "OK"
else
    res = "KO"
end

EOS
        );

        $this->assertDumpEquals(<<<EOD
array:12 [
  0 => Iamluc\Script\Token {
    -type: "if"
    -value: "if"
    -line: 1
    -column: 1
  }
  1 => Iamluc\Script\Token {
    -type: "name"
    -value: "toto"
    -line: 1
    -column: 4
  }
  2 => Iamluc\Script\Token {
    -type: "then"
    -value: "then"
    -line: 1
    -column: 9
  }
  3 => Iamluc\Script\Token {
    -type: "name"
    -value: "res"
    -line: 2
    -column: 5
  }
  4 => Iamluc\Script\Token {
    -type: "assign"
    -value: "="
    -line: 2
    -column: 9
  }
  5 => Iamluc\Script\Token {
    -type: "string"
    -value: "OK"
    -line: 2
    -column: 11
  }
  6 => Iamluc\Script\Token {
    -type: "else"
    -value: "else"
    -line: 3
    -column: 1
  }
  7 => Iamluc\Script\Token {
    -type: "name"
    -value: "res"
    -line: 4
    -column: 5
  }
  8 => Iamluc\Script\Token {
    -type: "assign"
    -value: "="
    -line: 4
    -column: 9
  }
  9 => Iamluc\Script\Token {
    -type: "string"
    -value: "KO"
    -line: 4
    -column: 11
  }
  10 => Iamluc\Script\Token {
    -type: "end"
    -value: "end"
    -line: 5
    -column: 1
  }
  11 => Iamluc\Script\Token {
    -type: "end of file"
    -value: null
    -line: 6
    -column: 1
  }
]
EOD
            , $lexer->all());
    }
}
