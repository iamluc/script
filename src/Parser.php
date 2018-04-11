<?php

namespace Iamluc\Script;

class Parser
{
    private $mathOperators = [
        Token::T_PLUS => 10,
        Token::T_MINUS => 10,
        Token::T_STAR => 20,
        Token::T_SLASH => 20,
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

        if ($token->is(Token::T_IF)) {
            return $this->parseIf($stream);
        }

        if ($token->is(Token::T_WHILE)) {
            return $this->parseWhile($stream);
        }

        if ($token->isVariable() && $stream->nextIs(Token::T_ASSIGN)) {
            $stream->peek(); // consume "="

            return $this->parseAssign($stream, $token);
        }

        return $this->parseExpression($stream, $token);
    }

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
                    throw new \LogicException(sprintf('Expected a scalar or a variable as start of the expression. Got "%s"', $token->getType()));
                }
                $left = $this->convertToNode($token);
        }

        while (($next = $stream->next())
            && $next->isMathOperator()
            && ($this->mathOperators[$next->getType()] > $precedence || 0 === $precedence)
        ) {
            $operation = $stream->peek();
            $right = $this->parseExpression($stream, $stream->peek(), $this->mathOperators[$operation->getType()]);

            $left = new Node\MathNode($left, $operation->getValue(), $right);
        }

        return $left;
    }

    private function parseWhile(TokenStream $stream)
    {
        $condition = $this->parseCondition($stream,Token::T_DO);

        $stream->expect(Token::T_DO);

        $body = $this->parseBlock($stream, [Token::T_END]);

        $stream->expect(Token::T_END);

        return new Node\WhileNode($condition, $body);
    }

    private function parseIf(TokenStream $stream, $end = true)
    {
        $condition = $this->parseCondition($stream, Token::T_THEN);

        $stream->expect(Token::T_THEN);

        $if = $this->parseBlock($stream, [Token::T_ELSE, Token::T_ELSEIF, Token::T_END]);

        while ($stream->nextIs([Token::T_ELSE, Token::T_ELSEIF])) {
            $token = $stream->peek(); // consume else/elseif

            if ($token->is(Token::T_ELSE)) {
                $else = $this->parseBlock($stream, [Token::T_ELSE, Token::T_ELSEIF, Token::T_END]);
            } else {
                $else = $this->parseIf($stream, false);
            }

            $if = new Node\ConditionalNode($condition, $if, $else);
        }

        if ($end) {
            $stream->expect(Token::T_END);
        }

        return $if;
    }

    private function parseCondition(TokenStream $stream, $end): Node\Node
    {
        $left = $this->parseExpression($stream, $stream->peek());

        if ($stream->nextIs($end)) {
            return $left;
        }

        $operator = $stream->peek();
        if (!$operator->isOperator()) {
            throw new \LogicException(sprintf('Expected an operator, got "%s".', $operator->getType()));
        }

        $right = $this->parseExpression($stream, $stream->peek());

        return new Node\ComparisonNode($left, $operator->getValue(), $right);
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
