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
    public function testLexerTypes($stream, $type, $value, $position)
    {
        $lexer = new Lexer($stream);
        $token = $lexer->next();

        $this->assertInstanceOf(Token::class, $token);
        $this->assertEquals($type, $token->getType());
        $this->assertEquals($value, $token->getValue());
        $this->assertEquals($position, $token->getPosition());
    }

    public function provideLexerTypes()
    {
        yield ['my_var', Token::T_NAME, 'my_var', 0];
        yield ['"my simple string"', Token::T_STRING, 'my simple string', 0];
        yield ['  =  ', Token::T_ASSIGN, '=', 2];
        yield [' if ', Token::T_IF, 'if', 1];
        yield ['then', Token::T_THEN, 'then', 0];
        yield ['else ', Token::T_ELSE, 'else', 0];
        yield ['end ', Token::T_END, 'end', 0];
        yield ['', Token::T_EOF, null, null];
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
    -position: 0
  }
  1 => Iamluc\Script\Token {
    -type: "assign"
    -value: "="
    -position: 6
  }
  2 => Iamluc\Script\Token {
    -type: "string"
    -value: "Salut"
    -position: 8
  }
  3 => Iamluc\Script\Token {
    -type: "name"
    -value: "world"
    -position: 16
  }
  4 => Iamluc\Script\Token {
    -type: "assign"
    -value: "="
    -position: 22
  }
  5 => Iamluc\Script\Token {
    -type: "string"
    -value: "le monde !"
    -position: 24
  }
  6 => Iamluc\Script\Token {
    -type: "end of file"
    -value: null
    -position: null
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
    -position: 0
  }
  1 => Iamluc\Script\Token {
    -type: "name"
    -value: "toto"
    -position: 3
  }
  2 => Iamluc\Script\Token {
    -type: "then"
    -value: "then"
    -position: 8
  }
  3 => Iamluc\Script\Token {
    -type: "name"
    -value: "res"
    -position: 17
  }
  4 => Iamluc\Script\Token {
    -type: "assign"
    -value: "="
    -position: 21
  }
  5 => Iamluc\Script\Token {
    -type: "string"
    -value: "OK"
    -position: 23
  }
  6 => Iamluc\Script\Token {
    -type: "else"
    -value: "else"
    -position: 28
  }
  7 => Iamluc\Script\Token {
    -type: "name"
    -value: "res"
    -position: 37
  }
  8 => Iamluc\Script\Token {
    -type: "assign"
    -value: "="
    -position: 41
  }
  9 => Iamluc\Script\Token {
    -type: "string"
    -value: "KO"
    -position: 43
  }
  10 => Iamluc\Script\Token {
    -type: "end"
    -value: "end"
    -position: 48
  }
  11 => Iamluc\Script\Token {
    -type: "end of file"
    -value: null
    -position: null
  }
]
EOD
            , $lexer->all());
    }
}
