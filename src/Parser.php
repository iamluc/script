<?php

namespace Iamluc\Script;

use Iamluc\Script\Node\BlockNode;
use Iamluc\Script\Node\NoOperationNode;

class Parser
{
    /**
     * @see https://www.lua.org/pil/3.5.html
     */
    private $precedenceMap = [
        Token::T_AND => 10,
        Token::T_OR => 10,

        Token::T_EQUAL => 20,
        Token::T_NOT_EQUAL => 20,
        Token::T_LESS_THAN => 20,
        Token::T_GREATER_THAN => 20,
        Token::T_LESS_EQUAL => 20,
        Token::T_GREATER_EQUAL => 20,

        // TODO: .. => 30

        Token::T_PLUS => 40,
        Token::T_MINUS => 40,

        Token::T_STAR => 50,
        Token::T_SLASH => 50,

        // TODO: not / - (unary) => 60
        // TODO: ^ => 70
    ];

    public function parse(string $script)
    {
        $stream = new TokenStream(new Lexer($script));

        return $this->parseBlock($stream, Token::T_EOF);
    }

    private function parseBlock(TokenStream $stream, $end): Node\BlockNode
    {
        $nodes = [];
        while (!$stream->nextIs($end)) {
            $nodes[] = $this->parseStatement($stream);
        }

        return new Node\BlockNode($nodes);
    }

    private function parseStatement(TokenStream $stream): Node\Node
    {
        $token = $stream->peek();

        if ($token->isEOF()) {
            throw new \LogicException('Unexptected end of file.');
        }

        if ($token->is(Token::T_FUNCTION)) {
            return $this->parseFunction($stream);
        }

        if ($token->is(Token::T_IF)) {
            return $this->parseIf($stream);
        }

        if ($token->is(Token::T_WHILE)) {
            return $this->parseWhile($stream);
        }

        if ($token->is(Token::T_RETURN)) { // FIXME: avoid repetition "return return true"
            return $this->parseReturn($stream);
        }

        if ($token->is(Token::T_BREAK)) {
            return $this->parseBreak($stream);
        }

        // Function call
        if ($token->isVariable() && $stream->nextIs(Token::T_LEFT_PAREN)) {
            return $this->parseCall($stream, $token);
        }

        if ($token->isVariable() && $stream->nextIs(Token::T_ASSIGN)) {
            $stream->peek(); // consume "="

            return $this->parseAssign($stream, $token);
        }

        return $this->parseExpression($stream, $token);
    }

    // FIXME: handle assignments in expression
    private function parseExpression(TokenStream $stream, Token $token, $precedence = 0)
    {
        switch ($token->getType()) {
            case Token::T_LEFT_PAREN:
                $left = $this->parseExpression($stream, $stream->peek());
                $stream->expect((Token::T_RIGHT_PAREN));
                break;

            case Token::T_MINUS: // negative
                $left = new Node\NegativeNode($this->parseExpression($stream, $stream->peek(), 100));
                break;

            case Token::T_PLUS: // positive, ignore
                $token = $stream->peek();
                // no break

            default:
                if (!$token->isScalar() && !$token->isVariable()) {
                    throw new \LogicException(sprintf('Expected a scalar, a variable or a function call as start of the expression. Got "%s"', $token->getType()));
                }

                // Function call
                if ($token->isVariable() && $stream->nextIs(Token::T_LEFT_PAREN)) {
                    $left = $this->parseCall($stream, $token);
                } else {
                    $left = $this->convertToNode($token);
                }
        }

        while (($next = $stream->next())
            && isset($this->precedenceMap[$next->getType()])
            && ($this->precedenceMap[$next->getType()] > $precedence || 0 === $precedence)
        ) {
            $operation = $stream->peek();
            $right = $this->parseExpression($stream, $stream->peek(), $this->precedenceMap[$operation->getType()]);

            if ($operation->isMathOperator()) {
                $left = new Node\MathNode($left, $operation->getValue(), $right);
            } elseif ($operation->isComparator()) {
                $left = new Node\ComparisonNode($left, $operation->getValue(), $right);
            } else {
                $left = new Node\LogicalNode($left, $operation->getValue(), $right);
            }
        }

        return $left;
    }

    private function parseWhile(TokenStream $stream)
    {
        $condition = $this->parseExpression($stream, $stream->peek());

        $stream->expect(Token::T_DO);

        $body = $this->parseBlock($stream, [Token::T_END]);

        $stream->expect(Token::T_END);

        return new Node\WhileNode($condition, $body);
    }

    private function parseIf(TokenStream $stream, $end = true)
    {
        $condition = $this->parseExpression($stream, $stream->peek());

        $stream->expect(Token::T_THEN);

        $if = $this->parseBlock($stream, [Token::T_ELSE, Token::T_ELSEIF, Token::T_END]);

        while ($stream->nextIs([Token::T_ELSE, Token::T_ELSEIF])) {
            $token = $stream->peek(); // consume else/elseif

            if ($token->is(Token::T_ELSE)) {
                $else = $this->parseBlock($stream, Token::T_END);
            } else {
                $else = $this->parseIf($stream, false);
            }

            $if = new Node\ConditionalNode($condition, $if, $else);
        }

        // if alone
        if ($if instanceof BlockNode) {
            $if = new Node\ConditionalNode($condition, $if, new NoOperationNode());
        }

        if ($end) {
            $stream->expect(Token::T_END);
        }

        return $if;
    }

    private function parseCall(TokenStream $stream, Token $name): Node\CallNode
    {
        $stream->expect(Token::T_LEFT_PAREN); // FIXME: handle arguments
        $stream->expect(Token::T_RIGHT_PAREN);

        return new Node\CallNode($name->getValue());
    }

    private function parseFunction(TokenStream $stream): Node\FunctionNode
    {
        $name = $stream->expect(Token::T_NAME);

        $stream->expect(Token::T_LEFT_PAREN); // FIXME: handle arguments
        $stream->expect(Token::T_RIGHT_PAREN);

        $block = $this->parseBlock($stream, Token::T_END);
        $stream->expect(Token::T_END);

        return new Node\FunctionNode($name->getValue(), null, $block);
    }

    private function parseReturn(TokenStream $stream): Node\ReturnNode
    {
        return new Node\ReturnNode($this->parseStatement($stream));
    }

    private function parseBreak(TokenStream $stream): Node\BreakNode
    {
        return new Node\BreakNode();
    }

    private function parseAssign(TokenStream $stream, Token $variable): Node\AssignNode
    {
        return new Node\AssignNode($variable->getValue(), $this->parseStatement($stream));
    }

    private function convertToNode(Token $token): Node\Node
    {
        if ($token->isScalar()) {
            return new Node\ScalarNode($token->getScalarValue());
        }

        if ($token->isVariable()) {
            return new Node\VariableNode($token->getValue());
        }

        throw new \LogicException(sprintf('Cannot convert token of type "%s" to node.', $token->getType()));
    }
}
